# Polaris

基于swoole的http开发框架，完全遵循psr7和psr15标准，并且支持基于fast-route路由的现代化php开发框架

## 依赖

- php >= 7.1
- ext-swoole >= 2.0 (建议使用最新4.x版本)

## 特点

- 基于[psr-7](https://www.php-fig.org/psr/psr-7/)的http请求&响应处理（基于Slim Http，在其基础上扩展了Swoole支持）
- 基于[psr-15](https://www.php-fig.org/psr/psr-15/)的中间件机制实现
- 基于[fast-route](https://github.com/nikic/FastRoute)的路由实现

