!function(t){var e={};function i(s){if(e[s])return e[s].exports;var r=e[s]={i:s,l:!1,exports:{}};return t[s].call(r.exports,r,r.exports,i),r.l=!0,r.exports}i.m=t,i.c=e,i.d=function(t,e,s){i.o(t,e)||Object.defineProperty(t,e,{enumerable:!0,get:s})},i.r=function(t){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(t,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(t,"__esModule",{value:!0})},i.t=function(t,e){if(1&e&&(t=i(t)),8&e)return t;if(4&e&&"object"==typeof t&&t&&t.__esModule)return t;var s=Object.create(null);if(i.r(s),Object.defineProperty(s,"default",{enumerable:!0,value:t}),2&e&&"string"!=typeof t)for(var r in t)i.d(s,r,function(e){return t[e]}.bind(null,r));return s},i.n=function(t){var e=t&&t.__esModule?function(){return t.default}:function(){return t};return i.d(e,"a",e),e},i.o=function(t,e){return Object.prototype.hasOwnProperty.call(t,e)},i.p="",i(i.s=20)}({20:function(t,e,i){var s,r,n,a,o=jQuery;window.wp=window.wp||{},a=wp.media=function(t){var e,i=a.view.MediaFrame;if(i)return"select"===(t=_.defaults(t||{},{frame:"select"})).frame&&i.Select?e=new i.Select(t):"post"===t.frame&&i.Post?e=new i.Post(t):"manage"===t.frame&&i.Manage?e=new i.Manage(t):"image"===t.frame&&i.ImageDetails?e=new i.ImageDetails(t):"audio"===t.frame&&i.AudioDetails?e=new i.AudioDetails(t):"video"===t.frame&&i.VideoDetails?e=new i.VideoDetails(t):"edit-attachments"===t.frame&&i.EditAttachments&&(e=new i.EditAttachments(t)),delete t.frame,a.frame=e,e},_.extend(a,{model:{},view:{},controller:{},frames:{}}),n=a.model.l10n=window._wpMediaModelsL10n||{},a.model.settings=n.settings||{},delete n.settings,s=a.model.Attachment=i(21),r=a.model.Attachments=i(22),a.model.Query=i(23),a.model.PostImage=i(24),a.model.Selection=i(25),a.compare=function(t,e,i,s){return _.isEqual(t,e)?i===s?0:i>s?-1:1:t>e?-1:1},_.extend(a,{template:wp.template,post:wp.ajax.post,ajax:wp.ajax.send,fit:function(t){var e,i=t.width,s=t.height,r=t.maxWidth,n=t.maxHeight;return _.isUndefined(r)||_.isUndefined(n)?_.isUndefined(n)?e="width":_.isUndefined(r)&&s>n&&(e="height"):e=i/s>r/n?"width":"height","width"===e&&i>r?{width:r,height:Math.round(r*s/i)}:"height"===e&&s>n?{width:Math.round(n*i/s),height:n}:{width:i,height:s}},truncate:function(t,e,i){return e=e||30,i=i||"&hellip;",t.length<=e?t:t.substr(0,e/2)+i+t.substr(-1*e/2)}}),a.attachment=function(t){return s.get(t)},r.all=new r,a.query=function(t){return new r(null,{props:_.extend(_.defaults(t||{},{orderby:"date"}),{query:!0})})},o(window).on("unload",function(){window.wp=null})},21:function(t,e){var i,s=Backbone.$;i=Backbone.Model.extend({sync:function(t,e,i){return _.isUndefined(this.id)?s.Deferred().rejectWith(this).promise():"read"===t?((i=i||{}).context=this,i.data=_.extend(i.data||{},{action:"get-attachment",id:this.id}),wp.media.ajax(i)):"update"===t?this.get("nonces")&&this.get("nonces").update?((i=i||{}).context=this,i.data=_.extend(i.data||{},{action:"save-attachment",id:this.id,nonce:this.get("nonces").update,post_id:wp.media.model.settings.post.id}),e.hasChanged()&&(i.data.changes={},_.each(e.changed,function(t,e){i.data.changes[e]=this.get(e)},this)),wp.media.ajax(i)):s.Deferred().rejectWith(this).promise():"delete"===t?((i=i||{}).wait||(this.destroyed=!0),i.context=this,i.data=_.extend(i.data||{},{action:"delete-post",id:this.id,_wpnonce:this.get("nonces").delete}),wp.media.ajax(i).done(function(){this.destroyed=!0}).fail(function(){this.destroyed=!1})):Backbone.Model.prototype.sync.apply(this,arguments)},parse:function(t){return t?(t.date=new Date(t.date),t.modified=new Date(t.modified),t):t},saveCompat:function(t,e){var i=this;return this.get("nonces")&&this.get("nonces").update?wp.media.post("save-attachment-compat",_.defaults({id:this.id,nonce:this.get("nonces").update,post_id:wp.media.model.settings.post.id},t)).done(function(t,s,r){i.set(i.parse(t,r),e)}):s.Deferred().rejectWith(this).promise()}},{create:function(t){return wp.media.model.Attachments.all.push(t)},get:_.memoize(function(t,e){return wp.media.model.Attachments.all.push(e||{id:t})})}),t.exports=i},22:function(t,e){var i=Backbone.Collection.extend({model:wp.media.model.Attachment,initialize:function(t,e){e=e||{},this.props=new Backbone.Model,this.filters=e.filters||{},this.props.on("change",this._changeFilteredProps,this),this.props.on("change:order",this._changeOrder,this),this.props.on("change:orderby",this._changeOrderby,this),this.props.on("change:query",this._changeQuery,this),this.props.set(_.defaults(e.props||{})),e.observe&&this.observe(e.observe)},_changeOrder:function(){this.comparator&&this.sort()},_changeOrderby:function(t,e){this.comparator&&this.comparator!==i.comparator||(e&&"post__in"!==e?this.comparator=i.comparator:delete this.comparator)},_changeQuery:function(t,e){e?(this.props.on("change",this._requery,this),this._requery()):this.props.off("change",this._requery,this)},_changeFilteredProps:function(t){this.props.get("query")||_.chain(t.changed).map(function(e,s){var r=i.filters[s],n=t.get(s);if(r){if(n&&!this.filters[s])this.filters[s]=r;else{if(n||this.filters[s]!==r)return;delete this.filters[s]}return!0}},this).any().value()&&(this._source||(this._source=new i(this.models)),this.reset(this._source.filter(this.validator,this)))},validateDestroyed:!1,validator:function(t){return!(!_.isUndefined(t.attributes.context)&&""!==t.attributes.context)&&(!(!this.validateDestroyed&&t.destroyed)&&_.all(this.filters,function(e){return!!e.call(this,t)},this))},validate:function(t,e){var i=this.validator(t),s=!!this.get(t.cid);return!i&&s?this.remove(t,e):i&&!s&&this.add(t,e),this},validateAll:function(t,e){return e=e||{},_.each(t.models,function(t){this.validate(t,{silent:!0})},this),e.silent||this.trigger("reset",this,e),this},observe:function(t){return this.observers=this.observers||[],this.observers.push(t),t.on("add change remove",this._validateHandler,this),t.on("reset",this._validateAllHandler,this),this.validateAll(t),this},unobserve:function(t){return t?(t.off(null,null,this),this.observers=_.without(this.observers,t)):(_.each(this.observers,function(t){t.off(null,null,this)},this),delete this.observers),this},_validateHandler:function(t,e,i){return i=e===this.mirroring?i:{silent:i&&i.silent},this.validate(t,i)},_validateAllHandler:function(t,e){return this.validateAll(t,e)},mirror:function(t){return this.mirroring&&this.mirroring===t?this:(this.unmirror(),this.mirroring=t,this.reset([],{silent:!0}),this.observe(t),this)},unmirror:function(){this.mirroring&&(this.unobserve(this.mirroring),delete this.mirroring)},more:function(t){var e=jQuery.Deferred(),i=this.mirroring,s=this;return i&&i.more?(i.more(t).done(function(){this===s.mirroring&&e.resolveWith(this)}),e.promise()):e.resolveWith(this).promise()},hasMore:function(){return!!this.mirroring&&this.mirroring.hasMore()},parse:function(t,e){return _.isArray(t)||(t=[t]),_.map(t,function(t){var i,s,r;return t instanceof Backbone.Model?(i=t.get("id"),t=t.attributes):i=t.id,r=(s=wp.media.model.Attachment.get(i)).parse(t,e),_.isEqual(s.attributes,r)||s.set(r),s})},_requery:function(t){var e;this.props.get("query")&&((e=this.props.toJSON()).cache=!0!==t,this.mirror(wp.media.model.Query.get(e)))},saveMenuOrder:function(){if("menuOrder"===this.props.get("orderby")){var t=this.chain().filter(function(t){return!_.isUndefined(t.id)}).map(function(t,e){return e+=1,t.set("menuOrder",e),[t.id,e]}).object().value();if(!_.isEmpty(t))return wp.media.post("save-attachment-order",{nonce:wp.media.model.settings.post.nonce,post_id:wp.media.model.settings.post.id,attachments:t})}}},{comparator:function(t,e,i){var s=this.props.get("orderby"),r=this.props.get("order")||"DESC",n=t.cid,a=e.cid;return t=t.get(s),e=e.get(s),"date"!==s&&"modified"!==s||(t=t||new Date,e=e||new Date),i&&i.ties&&(n=a=null),"DESC"===r?wp.media.compare(t,e,n,a):wp.media.compare(e,t,a,n)},filters:{search:function(t){return!this.props.get("search")||_.any(["title","filename","description","caption","name"],function(e){var i=t.get(e);return i&&-1!==i.search(this.props.get("search"))},this)},type:function(t){var e,i=this.props.get("type"),s=t.toJSON();return!(i&&(!_.isArray(i)||i.length))||(e=s.mime||s.file&&s.file.type||"",_.isArray(i)?_.find(i,function(t){return-1!==e.indexOf(t)}):-1!==e.indexOf(i))},uploadedTo:function(t){var e=this.props.get("uploadedTo");return!!_.isUndefined(e)||e===t.get("uploadedTo")},status:function(t){var e=this.props.get("status");return!!_.isUndefined(e)||e===t.get("status")}}});t.exports=i},23:function(t,e){var i,s=wp.media.model.Attachments;i=s.extend({initialize:function(t,e){var i;e=e||{},s.prototype.initialize.apply(this,arguments),this.args=e.args,this._hasMore=!0,this.created=new Date,this.filters.order=function(t){var e=this.props.get("orderby"),i=this.props.get("order");return!this.comparator||(this.length?1!==this.comparator(t,this.last(),{ties:!0}):"DESC"!==i||"date"!==e&&"modified"!==e?"ASC"===i&&"menuOrder"===e&&0===t.get(e):t.get(e)>=this.created)},i=["s","order","orderby","posts_per_page","post_mime_type","post_parent","author"],wp.Uploader&&_(this.args).chain().keys().difference(i).isEmpty().value()&&this.observe(wp.Uploader.queue)},hasMore:function(){return this._hasMore},more:function(t){var e=this;return this._more&&"pending"===this._more.state()?this._more:this.hasMore()?((t=t||{}).remove=!1,this._more=this.fetch(t).done(function(t){(_.isEmpty(t)||-1===this.args.posts_per_page||t.length<this.args.posts_per_page)&&(e._hasMore=!1)})):jQuery.Deferred().resolveWith(this).promise()},sync:function(t,e,i){var r;return"read"===t?((i=i||{}).context=this,i.data=_.extend(i.data||{},{action:"query-attachments",post_id:wp.media.model.settings.post.id}),-1!==(r=_.clone(this.args)).posts_per_page&&(r.paged=Math.round(this.length/r.posts_per_page)+1),i.data.query=r,wp.media.ajax(i)):(s.prototype.sync?s.prototype:Backbone).sync.apply(this,arguments)}},{defaultProps:{orderby:"date",order:"DESC"},defaultArgs:{posts_per_page:40},orderby:{allowed:["name","author","date","title","modified","uploadedTo","id","post__in","menuOrder"],valuemap:{id:"ID",uploadedTo:"parent",menuOrder:"menu_order ID"}},propmap:{search:"s",type:"post_mime_type",perPage:"posts_per_page",menuOrder:"menu_order",uploadedTo:"post_parent",status:"post_status",include:"post__in",exclude:"post__not_in",author:"author"},get:function(){var t=[];return function(e,s){var r,n={},a=i.orderby,o=i.defaultProps,h=!!e.cache||_.isUndefined(e.cache);return delete e.query,delete e.cache,_.defaults(e,o),e.order=e.order.toUpperCase(),"DESC"!==e.order&&"ASC"!==e.order&&(e.order=o.order.toUpperCase()),_.contains(a.allowed,e.orderby)||(e.orderby=o.orderby),_.each(["include","exclude"],function(t){e[t]&&!_.isArray(e[t])&&(e[t]=[e[t]])}),_.each(e,function(t,e){_.isNull(t)||(n[i.propmap[e]||e]=t)}),_.defaults(n,i.defaultArgs),n.orderby=a.valuemap[e.orderby]||e.orderby,h?r=_.find(t,function(t){return _.isEqual(t.args,n)}):t=[],r||(r=new i([],_.extend(s||{},{props:e,args:n})),t.push(r)),r}}()}),t.exports=i},24:function(t,e){var i=Backbone.Model.extend({initialize:function(t){var e=wp.media.model.Attachment;this.attachment=!1,t.attachment_id&&(this.attachment=e.get(t.attachment_id),this.attachment.get("url")?(this.dfd=jQuery.Deferred(),this.dfd.resolve()):this.dfd=this.attachment.fetch(),this.bindAttachmentListeners()),this.on("change:link",this.updateLinkUrl,this),this.on("change:size",this.updateSize,this),this.setLinkTypeFromUrl(),this.setAspectRatio(),this.set("originalUrl",t.url)},bindAttachmentListeners:function(){this.listenTo(this.attachment,"sync",this.setLinkTypeFromUrl),this.listenTo(this.attachment,"sync",this.setAspectRatio),this.listenTo(this.attachment,"change",this.updateSize)},changeAttachment:function(t,e){this.stopListening(this.attachment),this.attachment=t,this.bindAttachmentListeners(),this.set("attachment_id",this.attachment.get("id")),this.set("caption",this.attachment.get("caption")),this.set("alt",this.attachment.get("alt")),this.set("size",e.get("size")),this.set("align",e.get("align")),this.set("link",e.get("link")),this.updateLinkUrl(),this.updateSize()},setLinkTypeFromUrl:function(){var t,e=this.get("linkUrl");e?(t="custom",this.attachment?this.attachment.get("url")===e?t="file":this.attachment.get("link")===e&&(t="post"):this.get("url")===e&&(t="file"),this.set("link",t)):this.set("link","none")},updateLinkUrl:function(){var t;switch(this.get("link")){case"file":t=this.attachment?this.attachment.get("url"):this.get("url"),this.set("linkUrl",t);break;case"post":this.set("linkUrl",this.attachment.get("link"));break;case"none":this.set("linkUrl","")}},updateSize:function(){var t;if(this.attachment){if("custom"===this.get("size"))return this.set("width",this.get("customWidth")),this.set("height",this.get("customHeight")),void this.set("url",this.get("originalUrl"));(t=this.attachment.get("sizes")[this.get("size")])&&(this.set("url",t.url),this.set("width",t.width),this.set("height",t.height))}},setAspectRatio:function(){var t;this.attachment&&this.attachment.get("sizes")&&(t=this.attachment.get("sizes").full)?this.set("aspectRatio",t.width/t.height):this.set("aspectRatio",this.get("customWidth")/this.get("customHeight"))}});t.exports=i},25:function(t,e){var i,s=wp.media.model.Attachments;i=s.extend({initialize:function(t,e){s.prototype.initialize.apply(this,arguments),this.multiple=e&&e.multiple,this.on("add remove reset",_.bind(this.single,this,!1))},add:function(t,e){return this.multiple||this.remove(this.models),s.prototype.add.call(this,t,e)},single:function(t){var e=this._single;return t&&(this._single=t),this._single&&!this.get(this._single.cid)&&delete this._single,this._single=this._single||this.last(),this._single!==e&&(e&&(e.trigger("selection:unsingle",e,this),this.get(e.cid)||this.trigger("selection:unsingle",e,this)),this._single&&this._single.trigger("selection:single",this._single,this)),this._single}}),t.exports=i}});