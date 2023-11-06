layui.use(['admin', 'jquery', 'layer','element'], function() {
    var $ = layui.jquery;
    var layer = layui.layer;
    var layelem = layui.element;
    var admin = layui.admin;
    // 框 架 初 始 化
    admin.render({
        "logo": {
            "title": _config.title,
            "image": _config.logo
        },
        "menu": {
            "data": _config.menu,
            "accordion": true,
            "control": false,
            "select": _config.menu_select,
            async: false
        },
        "tab": {
            "muiltTab": true,
            "keepState": false,
            "session": true,
            "tabMax": 30,
            "index": {
                id: "1",
                href: _config.home_url,
                title: "面板首页"
            }
        },
        "theme": {
            "defaultColor": "3",
            "defaultMenu": "dark-theme",
            "allowCustom": false
        },
        "colors": [
            {
                "id": "3",
                "color": "#1E9FFF"
            }
        ],
        "links": [],
        "other": {
            "keepLoad": 1000
        },
        "header":{
            message: false
        }
    });

    layelem.on('nav(layui_nav_right)', function(elem) {
        if ($(elem).hasClass('logout')) {
            layer.confirm('确定退出登录吗?', function(index) {
                layer.close(index);
                $.ajax({
                    url: _config.logout_url,
                    type:"POST",
                    dataType:"json",
                    success: function(res) {
                        if (res.code==200) {
                            layer.msg(res.msg, {
                                icon: 1
                            });
                            setTimeout(function() {
                                location.href = _config.index_url;
                            }, 333)
                        }
                    }
                });
            });
        }else if ($(elem).hasClass('password')) {
            layer.open({
                type: 2,
                maxmin: true,
                title: '修改密码',
                shade: 0.1,
                area: ['300px', '300px'],
                content:_config.pass_url
            });
        }else if ($(elem).hasClass('cache')) {
            $.post(_config.clear_cache_url,
                function(data){
                    layer.msg(data.msg, {time: 1500});
                    location.reload()
                });

        }

    });
})