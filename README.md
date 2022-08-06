# Polaris

php开发框架，支持swoole，完全遵循psr7和psr15标准，并且支持基于fast-route路由的现代化php开发框架

## 依赖

- php ^ 7.4

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
