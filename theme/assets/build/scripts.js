const Twee={modules:{},initApp:function(){Twee.initStyles(),window.addEventListener("load",Twee.initStyles),window.addEventListener("resize",Twee.initStyles),window.addEventListener("load",Twee.initModules),window.addEventListener("rocket-load",Twee.initModules),document.addEventListener("rocket-DOMContentLoaded",Twee.initModules),document.addEventListener("DOMContentLoaded",Twee.initModules)},initStyles:function(){let e=parseInt(window.innerWidth-document.documentElement.clientWidth);(isNaN(e)||e<0)&&(e=0),document.body.style.setProperty("--width-scrollbar",e+"px")},initModules:function(){var e=[];Object.getOwnPropertyNames(Twee.modules).forEach((function(t){let o=Twee.modules[t];o.attached||(jQuery(document).on("tw_init",o.selector,o.callback),o.attached=!0,e=e.concat(o.selector.split(",")))})),e.length>0&&(e=(e=e.map((function(e){return e.toString().trim()}))).filter((function(e,t,o){return o.indexOf(e)===t})),Twee.initModule(e.join(", ")))},initModule:function(e){jQuery(e).each((function(){jQuery(this).trigger("tw_init",[jQuery,jQuery(this)])}))},addModule:function(e,t,o,n=[],i=!1,a=0){t=t?t.toString():"html",void 0===this.modules[e]?this.modules[e]={attached:!1,selector:t,callback:function(t){let s=!0,r=t.currentTarget;n&&n.length>0&&n.forEach((function(e){void 0===window[e]&&(s=!1)})),s&&(i||Twee.runOnce(r,e,a))&&o.call(r,jQuery,jQuery(r),t)}}:console.warn("Module "+e+" is already added")},runOnce:function(e,t,o=0){let n="tw_"+(t=t||"element")+"_loaded";return o>0&&setTimeout((function(){e[n]=!1}),o),!e[n]&&(e[n]=!0,!0)},runLater:function(e,t){let o;return function(){clearTimeout(o),o=setTimeout(e.apply.bind(e,this,arguments),t)}},scrollOffset:function(){var e=jQuery(".header_box").height();if(document.body.classList.contains("admin-bar")){var t=window.innerWidth;t<=782&&t>=600?e+=46:t>782&&(e+=32)}return e},smoothScrollTo:function(e,t=1e3){var o=jQuery("html, body");if((e=jQuery(e)).length>0){var n=e.offset().top-Twee.scrollOffset()-20;if(e.attr("id")){var i=o.scrollTop();window.location.hash=e.attr("id"),o.scrollTop(i)}o.stop().animate({scrollTop:n},t)}},lockScroll:function(){document.body.classList.add("is_locked")},unlockScroll:function(){document.body.classList.remove("is_locked")},setCookie:function(e,t,o=365){let n=new Date;n.setTime(n.getTime()+24*o*60*60*1e3);let i="expires="+n.toUTCString();document.cookie=e.toString()+"="+t.toString()+";"+i+";path=/"},getCookie:function(e){e+="=";let t=decodeURIComponent(document.cookie).split(";");for(var o=0;o<t.length;o++){for(var n=t[o];" "===n.charAt(0);)n=n.substring(1);if(0===n.indexOf(e))return n.substring(e.length,n.length)}return""},getCookieValue:function(e){return getCookie(e).split("|")||[]},setCookieValue:function(e,t){return Array.isArray(t)||(t=[t]),t=t.filter((function(e){return e})),setCookie(e,t.join("|"),365)},addCookieValue:function(e,t){var o=getCookieValue(e);o.push(t.toString()),setCookieValue(e,o)},removeCookieValue:function(e,t){var o=getCookieValue(e);o=o.filter((function(e){return e!==t})),setCookieValue(e,o)},hasCookieValue:function(e,t){return-1!==getCookieValue(e).indexOf(t)}};Twee.initApp(),Twee.addModule("carousel","html",(function(e,t){e(".items.carousel",t).each((function(){if(Twee.runOnce(this,"carousel",500))return;let t=e(this),o=t.data("carousel"),n={},i={infinite:!0,center:!1,transition:"slide",slidesPerPage:1,classes:{container:"carousel",viewport:"carousel-viewport",track:"carousel-track",slide:"item"},Dots:{classes:{list:"carousel-dots",isDynamic:"is-dynamic",hasDots:"has-dots",dot:"dot",isBeforePrev:"is-before-prev",isPrev:"is-prev",isCurrent:"is-current",isNext:"is-next",isAfterNext:"is-after-next"},dotTpl:'<button type="button" data-carousel-page="%i" aria-label="{{GOTO}}"></button>',dynamicFrom:3,minCount:3},Navigation:{classes:{container:"carousel-nav",button:"carousel-button",isNext:"is-next",isPrev:"is-prev"},nextTpl:"",prevTpl:""},on:{"ready change":function(t){t.slides.forEach((function(t){t.el.ariaHidden?e("a",t.el).attr("tabindex",-1):e("a",t.el).removeAttr("tabindex")}))}}};t.hasClass("gallery")&&(i.classes.slide="gallery-item",t.hasClass("gallery-columns-1")&&"undefined"!=typeof Thumbs&&(n={Thumbs:Thumbs},i.Dots=!1,i.Thumbs={type:"classic"})),t.on("refresh",(function(){o.reInit(i,n)})),"object"==typeof o?o.reInit(i,n):(o=new Carousel(t.get(0),i,n),t.data("carousel",o))}))}),["Carousel"],!0),Twee.addModule("comments",".comments_box",(function(e,t){var o=e("[data-comments]",t),n=o.data("comments"),i=t.find(".comments"),a=n.page===n.pages?-1:1;n.action="comment_list",n.noncer=tw_template.nonce,i.on("reset",(function(){i.children().remove(),n.page=1,o.trigger("click")})),o.on("click",(function(){n.page+=a,e.ajax(tw_template.ajaxurl,{type:"post",dataType:"html",data:n,beforeSend:function(){o.addClass("is_loading")}}).always((function(){o.removeClass("is_loading")})).done((function(a){if(a){var s=e(a).find(".comments").html();s?(i.append(e(s)),n.page<n.pages?o.removeClass("is_hidden"):o.addClass("is_hidden"),t.trigger("init")):o.addClass("is_hidden")}else o.addClass("is_hidden")}))}))})),Twee.addModule("fancybox","section",(function(){Fancybox.bind(this,'a[href*=".png"], a[href*=".jpg"], a[href*=".jpeg"], a[href*=".gif"], a[href*=".webp"]',{groupAll:!0}),Fancybox.bind(this,'a[href*="youtube.com"], a[href*="youtu.be"], a[href*="vimeo.com"], a[href*=".mp4"]',{})}),["Fancybox"]),Twee.addModule("forms",".form_box, .comment_box",(function(e,t){(t=t.hasClass("form_box")&&!t.is("form")?e("form",this):e("form.form_box, form.comment-form",this)).not(".skip_processing").each((function(){if(!runOnce(this,"forms")){var t,o=e(this),n=e('[type="submit"]',o);o.on("submit",(function(t){var a=o.serializeArray();if(0===e('[name="action"]',o).length){var s="email_handler";o.hasClass("comment-form")&&(s="comment_add"),a.push({name:"action",value:s})}return a.push({name:"noncer",value:tw_template.nonce}),e.ajax(tw_template.ajaxurl,{data:a,type:"post",dataType:"json",beforeSend:function(){n.prop("disabled",!0).addClass("is_loading")}}).always((function(){n.prop("disabled",!1).removeClass("is_loading")})).done(i),t.preventDefault(),t.stopPropagation(),!1})),o.on("change","input:file",(function(){var t=new FormData;return t.append("action","email_attachment"),t.append(this.name,this.files[0]),e.ajax(tw_template.ajaxurl,{type:"post",data:t,dataType:"json",processData:!1,contentType:!1,beforeSend:function(){n.prop("disabled",!0).addClass("is_loading")},xhr:function(){var e=new XMLHttpRequest;return e.upload.addEventListener("progress",(function(e){var t=0;e.lengthComputable&&e.total&&(t=Math.round(e.loaded/e.total*100),console.log("Uploading: "+t+"%"))}),!1),e}}).always((function(){n.prop("disabled",!1).removeClass("is_loading")})).done(i),!1})),o.on("click",".remove",(function(){var t=o.serializeArray();t.push({name:"action",value:"email_remove"}),t.push({name:"filename",value:e(this).data("name")}),t.push({name:"noncer",value:tw_template.nonce}),e.ajax(tw_template.ajaxurl,{data:t,type:"post",dataType:"json",beforeSend:function(){n.prop("disabled",!0).addClass("is_loading")}}).always((function(){n.prop("disabled",!1).removeClass("is_loading")})).done(i)}))}function i(n){if(e(".error, .success",o).remove(),n.link&&n.link.length>0&&(window.location.href=n.link),n.errors)for(let i in n.errors)n.errors.hasOwnProperty(i)&&(t=e('<div class="error">'+n.errors[i]+"</div>"),e("[name="+i+"]",o).closest(".field").append(t),t.hide().slideDown());if(n.files)for(let a in n.files)if(n.files.hasOwnProperty(a)){var i=e("[name="+a+"]",o).closest(".field");i.siblings(".notify").slideUp(400,(function(){e(this).remove()})),t=e('<div class="notify">'+n.files[a]+"</div>"),i.after(t),t.hide().slideDown()}n.text&&n.text.length>0&&(t=e('<div class="success">'+n.text+"</div>"),o.append(t),t.hide().slideDown(),o[0].reset())}})),t.on("input change","textarea",(function(){this.style.minHeight="initial",this.style.minHeight=this.scrollHeight+"px"}))})),Twee.addModule("header",".header_box",(function(e,t){var o=e(".submenu",t);function n(o,n){var i=["is_search","is_cart","is_search","is_menu"].filter((function(e){return e!==o}));t.removeClass(i.join(" ")),!o||t.hasClass(o)?(t.removeClass(o),"is_search"===o&&e('[name="s"]',t).blur(),unlockScroll()):(t.addClass(o),"is_search"===o&&e('[name="s"]',t).focus(),n&&lockScroll())}e(window).on("beforeunload pagehide",(function(){n(!1)})),t.on("click",".menu_btn",(function(e){n("is_menu",!0),e.preventDefault()})),t.on("click",".search_btn",(function(e){n("is_search",!0),e.preventDefault()})),t.nextAll("section").first().attr("id","contents"),t.on("click",(function(e){e.target===this&&n(!1)})),o.each((function(){var t=e(this),n=e("> ul",t),i=e("> a",t),a=e("> .back",n);i.click((function(e){window.innerWidth<=1024&&e.preventDefault()})),0===a.length&&(a=e('<li class="back">'+i.text()+"</li>"),n.prepend(a)),t.click((function(e){o.removeClass("is_active is_parent"),window.innerWidth<=1024&&(t.addClass("is_active").parents(".submenu").addClass("is_active is_parent"),o.find(".sub-menu").scrollTop(0)),e.stopPropagation()})),a.click((function(e){t.removeClass("is_active").parents(".submenu").removeClass("is_parent"),e.stopPropagation()}))}))})),Twee.addModule("modals","html",(function(e,t){e("[data-modal]").click((function(t){var o=e(this).data("modal");o&&e("#modal_"+o).trigger("open")})),e('a[href^="#modal_"]').click((function(t){e(e(this).attr("href")).trigger("open"),t.preventDefault()})),t.on("close",".modal_box",(function(){e(this).removeClass("is_visible"),Twee.unlockScroll()})),t.on("open",".modal_box",(function(){e(this).addClass("is_visible"),Twee.lockScroll()})),t.on("click",".modal_box",(function(){e(this).trigger("close")})),t.on("click",".modal_box .modal",(function(t){e(t.target).is(".close, [data-close]")?e(this).trigger("close"):t.stopPropagation()})),t.on("keyup",(function(t){27===t.which&&e(".modal_box").trigger("close")}))})),Twee.addModule("sticky","html",(function(e){let t=e(".header_box.is_sticky"),o=e(".header_box").get(0);function n(){let e=0,n=0,a=[],s=[];if(t.each((function(){let e=window.getComputedStyle(this,null),t=e.getPropertyValue("position"),o=e.getPropertyValue("bottom"),n=e.getPropertyValue("top");if("fixed"!==t&&"sticky"!==t)return;let i={element:this,rect:this.getBoundingClientRect(),top:!1,bottom:!1};"fixed"===t?(n=parseInt(n.replace("px","")),o=parseInt(o.replace("px","")),n<=o?(i.top=n,a.push(i)):(i.bottom=o,s.unshift(i))):-1!==n.indexOf("px")?(i.top=parseInt(n.replace("px","")),a.push(i)):-1!==o.indexOf("px")&&(i.bottom=parseInt(o.replace("px","")),s.unshift(i))})),a.length>0&&a.sort((function(e,t){return e.rect.top-t.rect.top})),s.length>0&&s.sort((function(e,t){return t.rect.top-e.rect.top})),a.concat(s).forEach((function(t){var o=t.element,i=t.rect,a=!1,s=e+"px";!1!==t.top?(o.style.getPropertyValue("--offset-top")!==s&&(o.style.setProperty("--offset-top",s),t.top=parseInt(window.getComputedStyle(o,null).getPropertyValue("top").replace("px",""))||0,i=o.getBoundingClientRect()),Math.abs(t.top-i.top)<1&&(e+=i.height,a=window.scrollY>0)):!1!==t.bottom&&(s=n+"px",o.style.getPropertyValue("--offset-bottom")!==s&&(o.style.setProperty("--offset-bottom",s),t.bottom=parseInt(window.getComputedStyle(o,null).getPropertyValue("bottom").replace("px",""))||0,i=o.getBoundingClientRect()),Math.abs(window.innerHeight-i.height-i.top-t.bottom)<1&&(n+=i.height,a=!0)),!a&&o.classList.contains("is_fixed")?o.classList.remove("is_fixed"):a&&!o.classList.contains("is_fixed")&&o.classList.add("is_fixed")})),i(document.body,"--offset-top",e+"px"),i(document.body,"--offset-bottom",n+"px"),o){var r=o.getBoundingClientRect();r.y>0?i(document.body,"--offset-header",r.y+"px"):i(document.body,"--offset-header","0px")}}function i(e,t,o){e.style.getPropertyValue(t)!==o&&e.style.setProperty(t,o)}window.addEventListener("scroll",n),window.addEventListener("load",n),n()})),Twee.addModule("tables","html",(function(e,t){e(".content > table",t).wrap('<div class="table"></div>')}));
//# sourceMappingURL=scripts.js.map
