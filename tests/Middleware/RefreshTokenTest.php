<?php

/*
 * This file is part of jwt-auth.
 *
 * (c) Sean Tymon <tymon148@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Middleware;

use Mockery;
use Tymon\JWTAuth\JWTAuth;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tymon\JWTAuth\Http\Parser\Parser;
use Tymon\JWTAuth\Test\AbstractTestCase;
use Tymon\JWTAuth\Http\Middleware\RefreshToken;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class RefreshTokenTest extends AbstractTestCase
{
    /**
     * @var \Mockery\MockInterface|\Tymon\JWTAuth\JWTAuth
     */
    protected $auth;

    /**
     * @var \Mockery\MockInterface|\Illuminate\Http\Request
     */
    protected $request;

    /**
     * @var \Tymon\JWTAuth\Http\Middleware\RefreshToken
     */
    protected $middleware;

    public function setUp()
    {
        parent::setUp();

        $this->auth = Mockery::mock(JWTAuth::class);
        $this->request = Mockery::mock(Request::class);

        $this->middleware = new RefreshToken($this->auth);
    }

    public function tearDown()
    {
        Mockery::close();

        parent::tearDown();
    }

    /** @test */
    public function it_should_refresh_a_token()
    {
        $parser = Mockery::mock(Parser::class);
        $parser->shouldReceive('hasToken')->once()->andReturn(true);

        $this->auth->shouldReceive('parser')->andReturn($parser);

        $this->auth->parser()->shouldReceive('setRequest')->once()->with($this->request)->andReturn($this->auth->parser());
        $this->auth->shouldReceive('parseToken->refresh')->once()->andReturn('foo.bar.baz');

        $response = $this->middleware->handle($this->request, function () {
            return new Response;
        });

        $this->assertSame($response->headers->get('authorization'), 'Bearer foo.bar.baz');
    }

    /**
     * @test
     * @expectedException \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     */
    public function it_should_throw_a_bad_request_exception_if_token_not_provided()
    {
        $parser = Mockery::mock(Parser::class);
        $parser->shouldReceive('hasToken')->once()->andReturn(false);

        $this->auth->shouldReceive('parser')->andReturn($parser);
        $this->auth->parser()->shouldReceive('setRequest')->once()->with($this->request)->andReturn($this->auth->parser());

        $this->middleware->handle($this->request, function () {
        });
    }

    /**
     * @test
     * @expectedException \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException
     */
    public function it_should_throw_an_unauthorized_exception_if_token_invalid()
    {
        $parser = Mockery::mock(Parser::class);
        $parser->shouldReceive('hasToken')->once()->andReturn(true);

        $this->auth->shouldReceive('parser')->andReturn($parser);

        $this->auth->parser()->shouldReceive('setRequest')->once()->with($this->request)->andReturn($this->auth->parser());
        $this->auth->shouldReceive('parseToken->refresh')->once()->andThrow(new TokenInvalidException);

        $this->middleware->handle($this->request, function () {
        });
    }
}
