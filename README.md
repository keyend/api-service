
<h1 align="left"><a href="https://assetin.balala.one/">API转发框架</a></h1>

## 安装
```
git clone git@github.com:keyend/api-service.git
```

## Requirement
1. 运行环境要求PHP7.1+，兼容PHP8.0。
2. Redis
3. 添加守护进程
4. 基于tp6
```
php think queue:listen --queue JobManager_101
```

## 主要特性

* 支持按次、按到期计费方式
* 支持更随意自由的充值
* 套餐变更时更简单
* 写法支持二次扩展
* 全新的事件系统

![添加会员](/public/static/images/snap/01.png)
![添加标签](/public/static/images/snap/02.png)
![会员等级](/public/static/images/snap/03.png)
![会员套餐](/public/static/images/snap/04.png)
![消息订单](/public/static/images/snap/05.png)
![财务记录](/public/static/images/snap/06.png)
![调用记录](/public/static/images/snap/06.png)

## 扩展

如需更多支持，请联系 keyend@163.com