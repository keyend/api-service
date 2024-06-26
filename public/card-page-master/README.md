# cardPage

#### 介绍
基于layui的扩展组件，分页卡片列表

#### 软件架构
软件架构说明


#### 安装教程

1.  引入layui
2.  引入cardPage.js和cardPage.css


#### 使用说明

      //配置介绍
        cols: [],//列名
        data: [],//数据
        colMd: 3,//列占行的宽度 3/12
        url: '',
        method: 'get',
        contentType: false,
        where: {},   // 查询条件
        sortName: '', //默认排序字段
        sortType: ''  //默认排序类型 asc desc
        data: ''      // 静态数据 如果存在静态数据 则不会走http查询



       // cols
        {
          field: 'name',   //字段名称 如果字段对象元素为数组 则需要加上 titleFomart与contextFomart函数处理对应标题与内容
          title: '商品名称', //标题
          topName: true    // 是否属于卡片顶部名称
          sort： false     // 是否可以排序
          fomart: function (item, value): //格式化内容 item：该行对象 value 该字段原有值
          titleFomart: function (item)  //字段对象元素为数组时生效 , item为数组中的对象元素 返回标题内容
          contextFomart: function (item) //字段对象元素为数组时生效 , item为数组中的对象元素 返回内容
        }

#### 效果图
![输入图片说明](https://images.gitee.com/uploads/images/2021/1230/152554_6a5444e0_2104653.png "屏幕截图.png")




