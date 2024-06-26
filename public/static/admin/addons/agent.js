layui.use(['table', 'form', 'dropdown', 'laytpl', 'laydate'], function(){
    var table = layui.table
    , dropdown = layui.dropdown
    , layer = top.layui.layer
    , laytpl = layui.laytpl
    , pageTable
    , form = layui.form
    , laydate = layui.laydate
    , active = {
        l: null,
        cols: [
            [{
                field: "agent_id",
                title: "ID",
                align: 'center',
                width: 50,
                fixed: "left",
                sort: true
            }, {
                title: "会员信息",
                minWidth: 200,
                fixed: "left",
                templet: '#table-userinfo'
            }, {
                field: "level_id",
                title: "代理等级",
                width: 160,
                templet: '#table-level'
            }, {
                field: "status",
                title: "状态",
                width: 90,
                align: 'center',
                templet: '#table-status'
            }, {
                field: "commission_money",
                title: "获得佣金",
                width: 120,
                templet(d) {
                    return '&yen;' + d.commission_money
                }
            }, {
                field: "create_time",
                title: "创建时间",
                width: 160,
                templet(d) {
                    return d.create_time == 0 ? '----' : d.create_time;
                }
            }, {
                title: "操作",
                width: 150,
                fixed: "right",
                toolbar: "#table-tool"
            }]
        ],
        add(){
            location.href = route.add
        },
        edit(e) {
            location.href = route.edit + "?id=" + e.data.agent_id
        },
        del(e) {
            layer.confirm('您确定要删除该代理？', {
                title: '友情提示',
                icon: 3,
                btn: ['是的', '再想想']
            }, function(i) {
                layer.close(i),
                ns.silent(route.del, {id: e.data.agent_id}, res => {
                    if (res.code == 0) {
                        setTimeout(() => pageTable.reload(), 100)
                    } else {
                        layer.alert(res.message)
                    }
                })
            });
        },
        status(id, status){
            ns.silent(route.status + '?id=' + id, {status: status?1:0});
        },
        refresh(o) {
            var othis = $(this).find('div'), anim = othis.data('anim');
            if (!othis.hasClass(anim)) {
                othis.removeClass(anim);
                othis.addClass(anim);
                setTimeout(() => {
                    pageTable.reload(),
                    othis.removeClass(anim)
                }, 1300)
            }
        },
        render() {
            pageTable = table.render({
                elem: "#LAY-list-table",
                url: location.href,
                toolbar: '#table-toolbar',
                cols: active.cols,
                response: { msgName: 'message' },
                page: !0,
                limit: 20,
                limits: [20, 50, 200, 500],
                height: "full-220",
                text: { none: "无数据" },
                parseData: function(res) {
                    return {
                        code: res.code,
                        msg: res.message,
                        count: res.data.count,
                        data: res.data.list
                    }
                },
                done: function() {
                    ns.page = this.page.curr;
                }
            }),

            table.on('toolbar(LAY-list-table)', function(o) {
                active[o.event] ? active[o.event].call(this, o) : '';
            }),

            table.on("tool(LAY-list-table)", function(e) {
                if (e.event == 'more') return dropdown.render(active.menus(e.data.membr_id, this, e));
                active[e.event] ? active[e.event].call(this, e) : '';
            }),

            table.on("pagebar(LAY-list-table)", function(e) {
                console.log(e);
            })
        }
    };

    jQuery = $ = layui.$,

    form.on('submit(LAY-btn-search)', function(data){
        var field = data.field;
        table.reload('LAY-list-table', { 
            where: field,
            page: { curr: 1 }
        });
        return false;
    }),

    form.on('switch(status)', function(obj){
        active.status(obj.elem.getAttribute('data-id'), obj.elem.checked)
    }),
    
    window.addEventListener("load", function() {
        active.render()
    });
})