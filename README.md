# Polaris

php开发框架，支持swoole，完全遵循psr7和psr15标准，并且支持基于fast-route路由的现代化php开发框架

## 依赖

- php >= 7.1

## 特点

- 基于[psr-7](https://www.php-fig.org/psr/psr-7/) 的http请求&响应处理（基于Slim Http，在其基础上扩展了Swoole支持）
- 基于[psr-15](https://www.php-fig.org/psr/psr-15/) 的中间件机制实现
- 基于[fast-route](https://github.com/nikic/FastRoute) 的路由实现

## 安装

安装依赖

```bash
$ cd 项目目录
$ composer require eslizn/polaris  -v
```

部署前，移除dev依赖并优化autoload：

```bash
$ composer update -v --no-dev
$ composer dump-autoload
```

## 使用

本文档将分别从自动加载、路由、中间件、请求处理、依赖注入、异常处理来介绍该框架的使用

### 自动加载

示例项目中基于[psr-4](https://www.php-fig.org/psr/psr-4/)规范进行自动加载，并会将根目录中的helpers.php引入，可参见composer.json中的：

```json
"autoload": {
	"psr-4": {
	  "Polaris\\": "src/"
	},
	"files": [
	  "helpers.php"
	]
  }
```

### 路由

路由默认配置文件在etc目录下的routes.php，路由定义方式：

```php
<?php
/**
 * 注释仅用于ide自动完成
 * 
 * 逻辑handler支持：
 * 1.$class@$method
 * 2.\Closure
 *
 * @var \Polaris\Http\Interfaces\RouterInterface $router
 */

/**system**/
//GET /  --->   HomeController::index
$router->get('/', 'HomeController@index');

//GET /alive  --->   \Closure
$router->get('/alive', function () {
	return 'ok';
});

//POST /users    --->  \Closure
$router->post('/users', function (\Psr\Http\Message\ServerRequestInterface $request) {
	//do something...
	return $request->getAttribute(User::class);
});

//路由多种METHOD
$router->map(['GET', 'POST', 'PUT', 'DELETE'], '/method', function (\Psr\Http\Message\ServerRequestInterface $request) {
	return $request->getMethod();
});

//分组路由
$router->group('/api', function (Polaris\Http\Interfaces\RouterInterface $r) {
	$r->get('/users', function () {
		//do something
	});
});
```

### 中间件

所谓中间件是指提供在请求和响应之间的，能够截获请求，并在其基础上进行逻辑处理，与此同时能够完成请求的响应或传递到下一个中间件的代码，工作流程如下图所示。

![Middleware](middleware.png)

#### 全局中间件

全局中间件是针对全局所有请求，默认配置文件在etc目录下的middleware.php，定义方式：

```php
<?php
/**
 *  根据业务需要自行配置
 *  
 *  支持：
 *  1.实现了\Psr\Http\Server\MiddlewareInterface对象名或实例（建议）
 *  2.闭包
 * 
 */
return [
	//跨域中间件，用于输出允许跨域的头信息
	\App\Http\Middlewares\CorsMiddleware::class,
	//异常捕获中间件，用于处理请求过程中的异常，根据业务需要特异化输出
	\App\Http\Middlewares\ExceptionMiddleware::class,
	//业务鉴权中间件，业务自行实现
	\App\Http\Middlewares\AuthMiddleware::class,
	//流控、验证码等等
];
```

#### 路由中间件

路由中间件仅针对路由中配置了的对应请求，配置在路由中，如：


```php
<?php
/**
 * 注释仅用于ide自动完成
 *
 * @var \Polaris\Http\Interfaces\RouterInterface $router
 */

/**system**/
//GET /  --->   HomeController::index
$router->get('/', 'HomeController@index', 
	\App\Http\Middlewares\AuthMiddleware::class,
	\App\Http\Middlewares\CorsMiddleware::class,
	...
);
```

### 请求处理

请求和响应均遵循psr-7标准，其意义在于标准化对http对象的统一，可适配不同的server，并大大降低学习成本。

请求对象[\Psr\Http\Message\ServerRequestInterface](https://www.php-fig.org/psr/psr-7/#321-psrhttpmessageserverrequestinterface) 会自动注入到路由的handler中去，只需要在hander(及class的构造函数)的参数中声名即可

```php
<?php
/**
 * @var \Psr\Http\Message\ServerRequestInterface $request
 */
$request->getMethod();//获取请求方式
$request->getUri();//获取请求URI
$request->getHeaders();//获取请求头
$request->getQueryParams();//获取querystring
$request->getParsedBody();//根据获取Content-Type解析并获取body数据
$request->getCookieParams();//获取cookie
$request->getAttributes();//获取（由服务端注入的）请求上下文，作用于依赖注入

```

响应[Psr\Http\Message\ResponseInterface](https://www.php-fig.org/psr/psr-7/#33-psrhttpmessageresponseinterface) 响应是在逻辑handler处理完成后返回的对象，会交由server来决定如何处理响应（状态码、响应头、响应内容等），可以自行构造response对象，框架也会根据数据自动构造response对象，内置的响应对象有:

- \Polaris\Http\Response 基础response，根据参数输出状态码、响应头和body
- \Polaris\Http\Response\FileResponse 文件response，根据参数输出对应的文件
- \Polaris\Http\Response\HtmlResponse 在基础response上提供了一个简单的渲染机制
- \Polaris\Http\Response\JsonResponse 根据参数输出json数据
- \Polaris\Http\Response\RedirectResponse 根据参数输出重定向的header

```php
<?php
/**
 * @var \Polaris\Http\Interfaces\RouterInterface $router
 */
$router->get('/url', function (\Psr\Http\Message\ServerRequestInterface $request) {
	//无return 等同于 return null,
	//return null;  等同于 return new Response(200);
	//return 标量(is_scalar=true，数字、字符串等); 等同于 return new Response(200, null, 数据);
	//return 非标量(is_scalar=false, 数组、对象等); 等同于 return new JsonResponse(数据);
	return new Polaris\Http\Response(200, null, sprintf('%s: %s', $request->getMethod(), $request->getUri()->getPath()));
});
```

### 依赖注入

依赖注入基于 请求对象[\Psr\Http\Message\ServerRequestInterface](https://www.php-fig.org/psr/psr-7/#321-psrhttpmessageserverrequestinterface) 的getAttribute和withAttribute实现，可以根据需要在请求级别实现数据共享：

例如，ams日志对象是针对每个请求去进行的日志上报，那么我们可以在接收到请求后第一时间将日志对象注入到request中：

```php
<?php
/**
 *  
 * 	\Psr\Http\Server\MiddlewareInterface
 */
public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
{
	return $handler->handle($request->withAttribu('name', 'value'));
}
```

### 异常处理

异常处理分为系统异常和请求级异常，系统异常是指服务本身的异常，请求异常是指针对特定请求而产生的异常。

系统异常处理：\Polaris\Http\Server对象的handleException方法默认会将错误日志输出到swoole的日志文件中，如果需要自定义，可以重载这个方法

请求级别异常：得益于middleware机制，只需要将异常处理middleware注册在可能会产生异常之前的位置就能进行捕获，并根据业务需要进行处理并返回：

```php
<?php
/**
 *  \Psr\Http\Server\MiddlewareInterface
 */
public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
{
	$logger = $request->getAttribute(LoggerInterface::class);
	try {
		return $handler->handle($request);
	} catch (HttpException $e) {
		return new Response($e->getStatusCode(), null, $e->getMessage());
	} catch (\Throwable $e) {
		if ($logger) {//如果存在日志对象，则上报日志
			$logger->error(sprintf('[%s]%s', get_class($e), $e->getMessage()));
		}
		return new Response(500, null, DEBUG ? $e->getMessage() : null);
	}
}
```

