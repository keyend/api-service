const ns = {
  /**
   * 解析参数
   * @param {*} str 
   * @returns 
   */
  parseUrl(str){
    str = str.split("?");
    if (str.length > 0) {
      var vars = str[1].split("&"), ret = {};
      vars.forEach(v => {
        v = v.split("="),
        ret[v[0]] = v[1]
      });
      return ret;
    }
    return {}
  },
  /**
   * 表单初始化
   * @returns void
   */
  init() {
    let jsx = document.getElementsByTagName("script"), 
    poi = jsx.length - 1, 
    url = jsx[poi].getAttribute("src"),
    vars = this.parseUrl(url);
    jQuery.extend(this, vars)
  },
  url(str, scheme, host) {
    host = host || location.host,
    scheme = (scheme || location.protocol) + '//';
    return scheme + host + "/" + this.service + "/" + this.code + '/' + (this.name?this.name + '/':'') + str + ".html"
  }
};
ns.init();