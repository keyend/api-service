;layui.define(function(e) {
    layui.use(['admin', 'jquery', 'layer','element'], function() {
        var $ = layui.jquery
        , layer = layui.layer
        , layelem = layui.element
        , admin = layui.admin;
        $.extend(admin, {
            indexPage() {
                $('#LAY_app_tabsheader').children().eq(0).trigger('click')
            }
        }),
        $('[layadmin-event="indexPage"]').on('click', function() {
            layui.admin.indexPage(),
            setTimeout(() => {
                $('.layui-nav-tree').children().eq(0).addClass("layui-this")
            },100)
        }),
        layelem.on('nav(layadmin-layout-right)', function(elem) {
            if ($(elem).hasClass('logout')) {
                layer.confirm('确定退出登录吗?', function(index) {
                    layer.close(index);
                    $.ajax({
                        url: app.logout_url,
                        type:"POST",
                        dataType:"json",
                        success: function(res) {
                            if (res.code == 0) {
                                layer.msg(res.message, {
                                    icon: 1
                                });
                                setTimeout(function() {
                                    location.href = app.index_url;
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
                    area: ['600px', '370px'],
                    content:app.pass_url
                });
            }else if ($(elem).hasClass('cache')) {
                layer.load(2),
                $.getJSON(app.clear_cache_url,
                    function(r){
                        layer.closeAll();
                        r.code != 0 ? layer.alert(r.message) : layer.msg("SUCCESS", { time: 1200, success() {
                            location.reload()
                        } });
                    });
            }

        });
    }),
    e("boostrap", {})
});