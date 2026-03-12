/**
 * Simditor v2.3.21
 * http://simditor.tower.im/
 *
 * Released under the MIT license
 */

(function (root, factory) {
  if (typeof define === 'function' && define.amd) {
    define([
      'jquery',
      './module',
      './toolbar',
      './selection',
      './keystroke',
      './hotkeys',
      './autosave',
      './autoresize',
      './paste',
      './uploader',
      './util'
    ], factory);
  } else if (typeof exports === 'object') {
    module.exports = factory(
      require('jquery'),
      require('./module'),
      require('./toolbar'),
      require('./selection'),
      require('./keystroke'),
      require('./hotkeys'),
      require('./autosave'),
      require('./autoresize'),
      require('./paste'),
      require('./uploader'),
      require('./util')
    );
  } else {
    factory(
      root.jQuery,
      root.Simditor,
      root.Simditor,
      root.Simditor,
      root.Simditor,
      root.Simditor,
      root.Simditor,
      root.Simditor,
      root.Simditor,
      root.Simditor
    );
  }
}(this, function ($, Simditor) {
  'use strict';

  var util = Simditor.util || {};

  // Simditor 核心类
  var Editor = function (options) {
    this.opts = $.extend({}, Editor.defaults, options || {});
    this._initContainer();
    this._initIframe();
    this._initModules();
    this._initEvents();
    this._initContent();

    this.initialized = true;
    this.trigger('initialized');
  };

  // 默认配置
  Editor.defaults = {
    textarea: null,
    placeholder: '',
    toolbar: true,
    toolbarFloat: true,
    toolbarFloatOffset: 0,
    toolbarHidden: false,
    toolbarButtons: ['bold', 'italic', 'underline', 'strikethrough', '|', 'ol', 'ul', '|', 'link', 'image', 'hr', '|', 'indent', 'outdent', '|', 'fullscreen'],
    toolbarButtonSize: 'default',
    upload: false,
    pasteImage: false,
    cleanPaste: true,
    imageButton: ['upload', 'external'],
    defaultImage: 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7',
    tabIndent: true,
    indentation: '2em',
    params: {},
    uploadUrl: null,
    uploadParams: {},
    uploadFileKey: 'upload_file',
    connectionCount: 3,
    leaveConfirm: 'You have unsaved changes, are you sure to leave?',
    autosave: false,
    autosaveInterval: 30000,
    autosaveKey: 'simditor-autosave-{id}',
    html: true,
    quickInsert: null,
    codeMirror: false,
    codeMirrorOptions: {},
    spellcheck: true,
    resize: true,
    minHeight: 100,
    maxHeight: null,
    lineHeight: '1.6',
    allowedTags: [
      'br', 'span', 'a', 'img', 'b', 'strong', 'i', 'em', 'strike', 'u', 'font',
      'p', 'div', 'pre', 'code', 'blockquote', 'ul', 'ol', 'li', 'h1', 'h2', 'h3',
      'h4', 'h5', 'h6', 'hr', 'table', 'thead', 'tbody', 'tr', 'td', 'th'
    ],
    allowedAttributes: {
      '*': ['style', 'class'],
      'a': ['href', 'target', 'rel', 'title'],
      'img': ['src', 'alt', 'title', 'width', 'height', 'data-original'],
      'font': ['color', 'size', 'face'],
      'table': ['border', 'cellspacing', 'cellpadding'],
      'td': ['rowspan', 'colspan'],
      'th': ['rowspan', 'colspan']
    },
    allowedStyles: {
      '*': ['text-align', 'margin', 'margin-top', 'margin-bottom', 'margin-left', 'margin-right'],
      'p': ['line-height'],
      'span': ['color', 'background-color', 'font-size', 'font-family', 'text-decoration', 'vertical-align'],
      'div': ['color', 'background-color', 'font-size', 'font-family', 'text-decoration'],
      'table': ['width', 'height', 'border-collapse', 'border-spacing'],
      'td': ['width', 'height', 'text-align', 'vertical-align'],
      'th': ['width', 'height', 'text-align', 'vertical-align']
    },
    modules: ['toolbar', 'hotkeys', 'uploader', 'paste', 'autosave', 'autoresize', 'keystroke', 'quickInsert']
  };

  // 原型方法扩展
  $.extend(Editor.prototype, $.eventEmitter, {
    // 初始化容器
    _initContainer: function () {
      this.id = util.guid('simditor');
      this.textarea = $(this.opts.textarea);
      if (!this.textarea.length) {
        throw new Error('Simditor: textarea element not found');
      }

      this.wrapper = $('<div class="simditor-wrapper"></div>').insertAfter(this.textarea);
      this.textarea.appendTo(this.wrapper).hide();

      this.editor = $('<div class="simditor"></div>').appendTo(this.wrapper);
      this.bodyWrapper = $('<div class="simditor-body-wrapper"></div>').appendTo(this.editor);

      // 设置基础样式
      this.editor.css({
        minHeight: this.opts.minHeight,
        lineHeight: this.opts.lineHeight
      });
      if (this.opts.maxHeight) {
        this.editor.css('maxHeight', this.opts.maxHeight);
      }

      // 占位符
      if (this.opts.placeholder) {
        this.placeholder = $('<div class="simditor-placeholder">' + this.opts.placeholder + '</div>')
          .appendTo(this.bodyWrapper)
          .css('lineHeight', this.opts.lineHeight);
      }
    },

    // 初始化 iframe 编辑区域
    _initIframe: function () {
      var self = this;
      this.iframe = $('<iframe class="simditor-body" frameborder="0" allowtransparency="true"></iframe>').appendTo(this.bodyWrapper);
      this.iframeDoc = this.iframe[0].contentDocument || this.iframe[0].contentWindow.document;

      // 初始化 iframe 文档
      this.iframeDoc.open();
      this.iframeDoc.write('<!DOCTYPE html><html><head><meta charset="UTF-8"><style>body {margin:0;padding:8px;outline:none;word-wrap:break-word;line-height:' + this.opts.lineHeight + ';}</style></head><body spellcheck="' + (this.opts.spellcheck ? 'true' : 'false') + '"></body></html>');
      this.iframeDoc.close();

      this.body = $(this.iframeDoc.body);
      this.window = this.iframe[0].contentWindow;

      // 同步 body 样式
      this.body.css({
        'min-height': this.opts.minHeight - 16 + 'px',
        'max-height': this.opts.maxHeight ? (this.opts.maxHeight - 16) + 'px' : 'none',
        'line-height': this.opts.lineHeight
      });

      // 监听 iframe 加载完成
      this.iframe.on('load', function () {
        self.trigger('bodyLoaded');
      });
    },

    // 初始化模块
    _initModules: function () {
      this.modules = {};
      var moduleNames = this.opts.modules;

      for (var i = 0, len = moduleNames.length; i < len; i++) {
        var name = moduleNames[i];
        this.modules[name] = Simditor.Module.create(name, this, this.opts[name]);
      }

      // 快捷访问核心模块
      if (this.modules.toolbar) {
        this.toolbar = this.modules.toolbar;
      }
      if (this.modules.selection) {
        this.selection = this.modules.selection;
      }
    },

    // 初始化事件
    _initEvents: function () {
      var self = this;

      // 监听 body 点击/输入事件
      this.body.on('click keydown keyup input', function (e) {
        self.trigger('body-' + e.type, e);
        self._checkPlaceholder();
      });

      // 窗口滚动/调整大小
      $(window).on('resize scroll', function () {
        self.trigger('window-' + e.type, e);
      });

      // 离开页面提示
      if (this.opts.leaveConfirm) {
        $(window).on('beforeunload', function (e) {
          if (self.hasChanged()) {
            var message = self.opts.leaveConfirm;
            e.returnValue = message;
            return message;
          }
        });
      }

      // 调整大小
      if (this.opts.resize) {
        this._initResize();
      }
    },

    // 初始化内容
    _initContent: function () {
      var content = this.textarea.val() || '';
      if (content) {
        this.setValue(content);
      }
      this.originalValue = this.getValue();
      this._checkPlaceholder();
    },

    // 初始化调整大小
    _initResize: function () {
      var self = this;
      var resizer = $('<div class="simditor-resizer"></div>').appendTo(this.editor);

      resizer.on('mousedown', function (e) {
        e.preventDefault();
        var startY = e.pageY;
        var startHeight = self.editor.height();

        $(document).on('mousemove.simditor-resize', function (e) {
          var deltaY = e.pageY - startY;
          var newHeight = startHeight + deltaY;
          if (newHeight < self.opts.minHeight) {
            newHeight = self.opts.minHeight;
          }
          if (self.opts.maxHeight && newHeight > self.opts.maxHeight) {
            newHeight = self.opts.maxHeight;
          }
          self.editor.height(newHeight);
          self.body.height(newHeight - 16);
        });

        $(document).on('mouseup.simditor-resize', function () {
          $(document).off('.simditor-resize');
        });
      });
    },

    // 检查占位符显示/隐藏
    _checkPlaceholder: function () {
      if (!this.placeholder) return;
      var content = this.body.text().trim();
      if (content) {
        this.placeholder.hide();
      } else {
        this.placeholder.show();
      }
    },

    // 获取编辑器内容（HTML）
    getValue: function () {
      var html = this.body.html() || '';
      if (!this.opts.html) {
        html = this.body.text();
      }
      return this._cleanHtml(html);
    },

    // 设置编辑器内容
    setValue: function (html) {
      this.body.html(html || '');
      this.textarea.val(this.getValue());
      this.originalValue = this.getValue();
      this._checkPlaceholder();
      this.trigger('valueChanged');
    },

    // 清理 HTML（过滤非法标签/属性/样式）
    _cleanHtml: function (html) {
      if (!html) return '';

      var $temp = $('<div></div>').html(html);
      var allowedTags = this.opts.allowedTags;
      var allowedAttributes = this.opts.allowedAttributes;
      var allowedStyles = this.opts.allowedStyles;

      // 过滤标签
      $temp.find('*').each(function () {
        var $el = $(this);
        var tag = this.tagName.toLowerCase();

        if ($.inArray(tag, allowedTags) === -1) {
          $el.replaceWith($el.html());
          return;
        }

        // 过滤属性
        var attrs = this.attributes;
        for (var i = attrs.length - 1; i >= 0; i--) {
          var attrName = attrs[i].name;
          var allowedAttrs = allowedAttributes[tag] || allowedAttributes['*'] || [];
          if ($.inArray(attrName, allowedAttrs) === -1) {
            $el.removeAttr(attrName);
          }
        }

        // 过滤样式
        var style = $el.attr('style') || '';
        var styleObj = {};
        $.each(style.split(';'), function (i, s) {
          s = s.trim();
          if (!s) return;
          var parts = s.split(':');
          var prop = parts[0].trim().toLowerCase();
          var value = parts.slice(1).join(':').trim();
          var allowedStylesList = allowedStyles[tag] || allowedStyles['*'] || [];
          if ($.inArray(prop, allowedStylesList) !== -1) {
            styleObj[prop] = value;
          }
        });

        $el.attr('style', '');
        $.each(styleObj, function (prop, value) {
          $el.css(prop, value);
        });
      });

      return $temp.html();
    },

    // 判断内容是否修改
    hasChanged: function () {
      return this.getValue() !== this.originalValue;
    },

    // 清空内容
    clear: function () {
      this.setValue('');
    },

    // 聚焦编辑器
    focus: function () {
      this.body.focus();
      this.trigger('focus');
    },

    // 失焦编辑器
    blur: function () {
      this.body.blur();
      this.trigger('blur');
    },

    // 撤销
    undo: function () {
      if (this.window.document.execCommand('undo', false, null)) {
        this.trigger('undo');
        this.textarea.val(this.getValue());
      }
    },

    // 重做
    redo: function () {
      if (this.window.document.execCommand('redo', false, null)) {
        this.trigger('redo');
        this.textarea.val(this.getValue());
      }
    },

    // 加粗
    bold: function () {
      this.window.document.execCommand('bold', false, null);
      this.trigger('format', 'bold');
      this.textarea.val(this.getValue());
    },

    // 斜体
    italic: function () {
      this.window.document.execCommand('italic', false, null);
      this.trigger('format', 'italic');
      this.textarea.val(this.getValue());
    },

    // 下划线
    underline: function () {
      this.window.document.execCommand('underline', false, null);
      this.trigger('format', 'underline');
      this.textarea.val(this.getValue());
    },

    // 删除线
    strikethrough: function () {
      this.window.document.execCommand('strikeThrough', false, null);
      this.trigger('format', 'strikethrough');
      this.textarea.val(this.getValue());
    },

    // 缩进
    indent: function () {
      this.window.document.execCommand('indent', false, null);
      this.trigger('format', 'indent');
      this.textarea.val(this.getValue());
    },

    // 取消缩进
    outdent: function () {
      this.window.document.execCommand('outdent', false, null);
      this.trigger('format', 'outdent');
      this.textarea.val(this.getValue());
    },

    // 销毁编辑器
    destroy: function () {
      // 销毁所有模块
      $.each(this.modules, function (name, module) {
        if (module && module.destroy) {
          module.destroy();
        }
      });

      // 移除事件监听
      $(window).off('.simditor-resize beforeunload');
      this.iframe.off('load');
      this.body.off();

      // 恢复 textarea
      this.textarea.show().insertAfter(this.wrapper);
      this.wrapper.remove();

      // 清空引用
      this.editor = null;
      this.body = null;
      this.iframe = null;
      this.wrapper = null;
      this.modules = null;

      this.trigger('destroyed');
    }
  });

  // 挂载到 Simditor 命名空间
  Simditor.Editor = Editor;

  // 快捷初始化方法
  $.fn.simditor = function (options) {
    return this.each(function () {
      var $this = $(this);
      if (!$this.data('simditor')) {
        options = $.extend({}, options, { textarea: this });
        $this.data('simditor', new Editor(options));
      }
    });
  };

  // 暴露全局 Simditor
  window.Simditor = Simditor;

  return Simditor;
}));