var ns = {
  // 当前窗口ID
  id: '',
  // 堆栈
  stock: [],
  // 缓存
  values: null,
  // 来源窗口
  from: null,
  // 当前页码
  page: 1,
  // 是否为子窗口
  ischild: 0,
  // 是否可重载
  isReload: false,
  /**
   * 静默方式POST
   * @param {*} url 
   * @param {*} data 
   * @param {*} fn 
   */
  silent(url, data, fn) {
    let loading = top.layer.load(2);
    layui.$.ajax({
      type: 'POST',
      url: url,
      data: data,
      dataType: 'json',
      success: res => {
        top.layer.close(loading);
        if (res.code != 0) {
          top.layer.msg(res.message || res.msg, {time: 2000});
        } else {
          parent.layer.msg("SUCCESS", {icon: 1, time: 800} , () => typeof(fn)=='function'&&fn.call(this, res))
        }
      }
    })
  },
  /**
   * 是否重载状态
   * @return boolean
   */
  getReload() {
    const ret = this.isReload;
    this.isReload = false;
    return ret
  },
  /**
   * 发送消息
   * @param {*} ds 
   */
  postMessage(ds) {
    ds.params.id && self.parent.frames[ds.params.id].contentWindow.postMessage(ds)
  },
  /**
   * 监听消息
   * @param {*} ds 
   */
  listener() {
    var that = this;
    window.addEventListener("message", receiveMessage, false);

    function receiveMessage(ev) {
      let res = ev.data, isReceived = false;
      console.log(that.id + "接收消息 => " + JSON.stringify(res));

      if (typeof(res) == 'object') {
        if (res.method == "wost") {
          isReceived = true,
          that.isReload = res.params.id == that.id && res.params.response.code == 0,
          window.removeEventListener("message", receiveMessage, false),
          console.log("监听窗口" + that.id + "子窗口 " + res.params.child_id + "__" + res.params.id + " ==> 提交完成");
        }
      }
    }
  },
  /**
   * IFRAME弹窗
   * @returns void
   */
  open(url, title, size, type) {
    return new Promise(resolve => {
      let obj = window.parent;
      this.listener(),
      obj.ns.stock.push({
        id: this.id,
        title: title
      }),
      obj.layer.open({
        type: type||2
        ,title: title || false
        ,shadeClose: title ? false : true
        ,content: url
        ,area: size||['60%', '60%']
        ,end: () => {
          const value = self.parent.ns.values;
          self.parent.ns.values = null,
          value && resolve(value)
        }
      })
    })
  },
  /**
   * 关闭当前弹窗
   */
  close(value) {
    var index = self.parent.layer.getFrameIndex(this.id);
    self.parent.layer.close(index);
  },
  /**
   * 获取当前表格选中字段
   * @param obj 表格回调参数
   * @param field 要获取的字段
   */
  tableSelected(obj, field) {
    if (typeof(layui.table) !== 'undefined') {
			let data = layui.table.checkStatus(obj.config.id).data;
			if (data.length === 0) {
				return "";
			}
      if (field) {
        let ids = [];
        for (let i = 0; i < data.length; i++) {
          ids.push(data[i][field])
        }
        data = ids;
      }
			return data;
    }
    return [];
  },
  /**
   * IFRAME弹窗POST
   * @param {*} url 
   * @param {*} data 
   * @param {*} fn 
   */
  wost(url, data, fn) {
    $.post(url, data, res => {
      self.parent.ns.values = {
        method: 'wost',
        params: {
          id: this.from ? this.from.id : '',
          child_id: this.id,
          url: url,
          data: data,
          response: res
        }
      },
      this.ischild && this.postMessage(self.parent.ns.values);
      if(0 == res.code) {
        res.message == 'success' ? parent.layer.msg("SUCCESS", {icon: 1, time: 800}, () => typeof(fn)=='function'&&fn.call(this, res)) : parent.layer.alert(res.message || res.msg, (index) => {
          parent.layer.close(index),
          parent.layer.msg("SUCCESS", {icon: 1, time: 800}, () => typeof(fn)=='function'&&fn.call(this, res))
        })
      } else {
        parent.layer.alert(res.message || res.msg, { 'icon': 2 });
      }
    }, 'json');
  },  
  /**
   * 图片地址转换
   * @param {*} url 
   */
  img(url) {
    if(url.indexOf('://')==-1){
      return location.origin+url
    }else{
      return url
    }
  },
  /**
   * 相册
   * @param {*} fn 
   */
  album(fn, limit) {
    limit=limit||9999;
    parent.layer.open({
			type: 2,
			title: '图片管理',
			area: ['825px', '675px'],
			fixed: false, //不固定
			btn: ['保存', '返回'],
			content: '/album/admin/album.html?limit=' + limit,
			yes: function (index, e) {
        e = e.find('iframe')[0].contentWindow,
				e.getCheckItem(obj => typeof(fn)=='function'&&fn.call(e, obj)),
        parent.layer.close(index)
			}
		})
  },
  /**
   * 添加上传组件
   * @param {*} v 
   */
  imgUploaderRender(v) {
    let that = $(v), limit = that.data('limit'), value = that.find('input[type="hidden"]').val();
    let template = $("#uploadImage").html();
    let values = value?value.split(','):[];
    let data = {
      list: values,
      max: limit
    };

    function render() {
      that.find('.inner').empty(),
      laytpl(template).render(data, function (html) {
        that.find('.inner').append(html),
        event()
      })
    }

    function event() {
      that.find('.js-add-image').on('click', function() {
        ns.album(res => {
          res.forEach(v => data.list.values.length<limit&&data.list.push(v.filepath)),
          that.find('input[type="hidden"]').val(data.list.join(',')),
          render()
        }, limit)
      }),

      that.find('[layer-src]').on('click', function(){
        let img = this,layout = img.parentNode;
        !layout.id&&(layout.id='img_' + (new Date()).getTime(),layout.setAttribute('id', layout.id)),
        layer.photos({ photos: {
          start: 0,
          data: [
            {
              pid: 1,
              src: img.src,
              thumb: img.src
            }
          ]
        }, anim: 5 });
      }),

      that.find('.js-preview').on('click', function() {
        $(this).parent().prev().find("img").trigger('click')
      }),

      that.find('.js-delete').on('click', function() {
				let index = this.getAttribute("data-index");
        data.list.splice(index, 1),
        that.find('input[type="hidden"]').val(data.list.join(',')),
        render()
      })
    }

    render()
  },

  /**
   * 分配窗口ID
   * @returns void
   */
  register() {
    this.ischild = self.frameElement && self.frameElement.tagName == 'IFRAME' ? 1 : 0;
    if (this.ischild) {
      this.id = self.frameElement.getAttribute("data-id");
      if (!this.id) {
        this.id = (new Date()).getTime(),
        self.frameElement.setAttribute("id", (new Date()).getTime()),
        self.frameElement.setAttribute("name", (new Date()).getTime())
      }
    } else {
      this.id = (new Date()).getTime()
    }
    console.log("注册" + (this.ischild ? '子' : '') + "窗口 " + this.id),
    this.ischild && self.parent.ns.stock.length > 0 && (this.from = self.parent.ns.stock.pop(), console.log("父窗口 " + this.from.id))
  },

  /**
   * 显示组件
   * @returns void
   */
  view() {
    $('.image-uploader').each((i,v) => this.imgUploaderRender(v))
  },

  /**
   * 表单初始化
   * @returns void
   */
  init() {
	  layui.use(['laytpl', 'layer'], () => {
      window.$ = layui.$,
      window.laytpl = layui.laytpl,
      this.register(),
      this.view()
    })
  }
}