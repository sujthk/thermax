AUI.add("aui-selector",function(c){var f=c.Lang,b=f.isString,h=c.Selector,d=c.getClassName,a=c.getClassName("helper","hidden"),g=new RegExp(a);h._isNodeHidden=function(m){var l=m.offsetWidth;var i=m.offsetHeight;var o=m.nodeName.toLowerCase()=="tr";var k=m.className;var j=m.style;var n=false;if(!o){if(l==0&&i==0){n=true;}else{if(l>0&&i>0){n=false;}}}n=n||(j.display=="none"||j.visibility=="hidden")||g.test(k);return n;};var e=function(i){return function(j){return j.type==i;};};c.mix(h.pseudos,{button:function(i){return i.type==="button"||i.nodeName.toLowerCase()==="button";},checkbox:e("checkbox"),checked:function(i){return i.checked===true;},disabled:function(i){return i.disabled===true;},empty:function(i){return !i.firstChild;},enabled:function(i){return i.disabled===false&&i.type!=="hidden";},file:e("file"),header:function(i){return/h\d/i.test(i.nodeName);},hidden:function(i){return h._isNodeHidden(i);},image:e("image"),input:function(i){return/input|select|textarea|button/i.test(i.nodeName);},parent:function(i){return !!i.firstChild;},password:e("password"),radio:e("radio"),reset:e("reset"),selected:function(i){i.parentNode.selectedIndex;return i.selected===true;},submit:e("submit"),text:e("text"),visible:function(i){return !h._isNodeHidden(i);}});},"@VERSION@",{requires:["selector-css3"],skinnable:false});