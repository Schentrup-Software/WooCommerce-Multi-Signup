(()=>{"use strict";var e={20:(e,o,t)=>{var l=t(609),r=Symbol.for("react.element"),i=Symbol.for("react.fragment"),a=Object.prototype.hasOwnProperty,n=l.__SECRET_INTERNALS_DO_NOT_USE_OR_YOU_WILL_BE_FIRED.ReactCurrentOwner,c={key:!0,ref:!0,__self:!0,__source:!0};function s(e,o,t){var l,i={},s=null,d=null;for(l in void 0!==t&&(s=""+t),void 0!==o.key&&(s=""+o.key),void 0!==o.ref&&(d=o.ref),o)a.call(o,l)&&!c.hasOwnProperty(l)&&(i[l]=o[l]);if(e&&e.defaultProps)for(l in o=e.defaultProps)void 0===i[l]&&(i[l]=o[l]);return{$$typeof:r,type:e,key:s,ref:d,props:i,_owner:n.current}}o.Fragment=i,o.jsx=s,o.jsxs=s},848:(e,o,t)=>{e.exports=t(20)},609:e=>{e.exports=window.React}},o={};const t=window.wp.components,l=window.wp.blocks,r=window.wp.blockEditor,i=window.wc.blocksCheckout,a=window.wp.element,n=window.wp.i18n,c=window.wp.data,s=e=>{let o=e;const t=d();for(const e in t)o=o.replace(new RegExp(e,"g"),t[e]);return o=o.replace(/\s/g,"_"),o=o.toLowerCase(),o=o.replace(/[^a-z0-9_]/gi,""),o=o.replace(/[/_]{2,}/g,"_"),o},d=()=>({"-":"_","/":"_",",":"_",":":"_",";":"_",ª:"a",º:"o",À:"A",Á:"A",Â:"A",Ã:"A",Ä:"A",Å:"A",Æ:"AE",Ç:"C",È:"E",É:"E",Ê:"E",Ë:"E",Ì:"I",Í:"I",Î:"I",Ï:"I",Ð:"D",Ñ:"N",Ò:"O",Ó:"O",Ô:"O",Õ:"O",Ö:"O",Ù:"U",Ú:"U",Û:"U",Ü:"U",Ý:"Y",Þ:"TH",ß:"s",à:"a",á:"a",â:"a",ã:"a",ä:"a",å:"a",æ:"ae",ç:"c",è:"e",é:"e",ê:"e",ë:"e",ì:"i",í:"i",î:"i",ï:"i",ð:"d",ñ:"n",ò:"o",ó:"o",ô:"o",õ:"o",ö:"o",ø:"o",ù:"u",ú:"u",û:"u",ü:"u",ý:"y",þ:"th",ÿ:"y",Ø:"O",Ā:"A",ā:"a",Ă:"A",ă:"a",Ą:"A",ą:"a",Ć:"C",ć:"c",Ĉ:"C",ĉ:"c",Ċ:"C",ċ:"c",Č:"C",č:"c",Ď:"D",ď:"d",Đ:"D",đ:"d",Ē:"E",ē:"e",Ĕ:"E",ĕ:"e",Ė:"E",ė:"e",Ę:"E",ę:"e",Ě:"E",ě:"e",Ĝ:"G",ĝ:"g",Ğ:"G",ğ:"g",Ġ:"G",ġ:"g",Ģ:"G",ģ:"g",Ĥ:"H",ĥ:"h",Ħ:"H",ħ:"h",Ĩ:"I",ĩ:"i",Ī:"I",ī:"i",Ĭ:"I",ĭ:"i",Į:"I",į:"i",İ:"I",ı:"i",Ĳ:"IJ",ĳ:"ij",Ĵ:"J",ĵ:"j",Ķ:"K",ķ:"k",ĸ:"k",Ĺ:"L",ĺ:"l",Ļ:"L",ļ:"l",Ľ:"L",ľ:"l",Ŀ:"L",ŀ:"l",Ł:"L",ł:"l",Ń:"N",ń:"n",Ņ:"N",ņ:"n",Ň:"N",ň:"n",ŉ:"n",Ŋ:"N",ŋ:"n",Ō:"O",ō:"o",Ŏ:"O",ŏ:"o",Ő:"O",ő:"o",Œ:"OE",œ:"oe",Ŕ:"R",ŕ:"r",Ŗ:"R",ŗ:"r",Ř:"R",ř:"r",Ś:"S",ś:"s",Ŝ:"S",ŝ:"s",Ş:"S",ş:"s",Š:"S",š:"s",Ţ:"T",ţ:"t",Ť:"T",ť:"t",Ŧ:"T",ŧ:"t",Ũ:"U",ũ:"u",Ū:"U",ū:"u",Ŭ:"U",ŭ:"u",Ů:"U",ů:"u",Ű:"U",ű:"u",Ų:"U",ų:"u",Ŵ:"W",ŵ:"w",Ŷ:"Y",ŷ:"y",Ÿ:"Y",Ź:"Z",ź:"z",Ż:"Z",ż:"z",Ž:"Z",ž:"z",ſ:"s",Ș:"S",ș:"s",Ț:"T",ț:"t","€":"E","£":"",Ơ:"O",ơ:"o",Ư:"U",ư:"u",Ầ:"A",ầ:"a",Ằ:"A",ằ:"a",Ề:"E",ề:"e",Ồ:"O",ồ:"o",Ờ:"O",ờ:"o",Ừ:"U",ừ:"u",Ỳ:"Y",ỳ:"y",Ả:"A",ả:"a",Ẩ:"A",ẩ:"a",Ẳ:"A",ẳ:"a",Ẻ:"E",ẻ:"e",Ể:"E",ể:"e",Ỉ:"I",ỉ:"i",Ỏ:"O",ỏ:"o",Ổ:"O",ổ:"o",Ở:"O",ở:"o",Ủ:"U",ủ:"u",Ử:"U",ử:"u",Ỷ:"Y",ỷ:"y",Ẫ:"A",ẫ:"a",Ẵ:"A",ẵ:"a",Ẽ:"E",ẽ:"e",Ễ:"E",ễ:"e",Ỗ:"O",ỗ:"o",Ỡ:"O",ỡ:"o",Ữ:"U",ữ:"u",Ỹ:"Y",ỹ:"y",Ấ:"A",ấ:"a",Ắ:"A",ắ:"a",Ế:"E",ế:"e",Ố:"O",ố:"o",Ớ:"O",ớ:"o",Ứ:"U",ứ:"u",Ạ:"A",ạ:"a",Ậ:"A",ậ:"a",Ặ:"A",ặ:"a",Ẹ:"E",ẹ:"e",Ệ:"E",ệ:"e",Ị:"I",ị:"i",Ọ:"O",ọ:"o",Ộ:"O",ộ:"o",Ợ:"O",ợ:"o",Ụ:"U",ụ:"u",Ự:"U",ự:"u",Ỵ:"Y",ỵ:"y",ɑ:"a",Ǖ:"U",ǖ:"u",Ǘ:"U",ǘ:"u",Ǎ:"A",ǎ:"a",Ǐ:"I",ǐ:"i",Ǒ:"O",ǒ:"o",Ǔ:"U",ǔ:"u",Ǚ:"U",ǚ:"u",Ǜ:"U",ǜ:"u"});var u=function t(l){var r=o[l];if(void 0!==r)return r.exports;var i=o[l]={exports:{}};return e[l](i,i.exports,t),i.exports}(848);const f=({attributes:e,setAttributes:o,children:l})=>{const{fieldName:r,metaName:i}=e;return(0,u.jsxs)(t.PanelBody,{title:(0,n.__)("General Settings","checkout-fields-for-blocks"),children:[(0,u.jsx)(t.TextControl,{label:(0,n.__)("Field name","checkout-fields-for-blocks"),value:r,onChange:e=>{o({fieldName:e,metaName:"_meta_"+s(e)})}}),(0,u.jsx)(t.TextControl,{label:(0,n.__)("Meta name","checkout-fields-for-blocks"),value:i}),l]})},g={required:(0,n.__)("Required","checkout-fields-for-blocks"),phone:(0,n.__)("Phone","checkout-fields-for-blocks"),email:(0,n.__)("Email","checkout-fields-for-blocks"),url:(0,n.__)("URL","checkout-fields-for-blocks"),vatNumber:(0,n.__)("VAT Number","checkout-fields-for-blocks"),minLength:(0,n.__)("Minimum Length","checkout-fields-for-blocks"),maxLength:(0,n.__)("Maximum Length","checkout-fields-for-blocks"),pattern:(0,n.__)("Pattern","checkout-fields-for-blocks")},h=["minLength","maxLength","pattern"],k=({validationSettings:e,setValidationSettings:o})=>(0,u.jsx)(t.PanelBody,{title:(0,n.__)("Validation","checkout-fields-for-blocks"),initialOpen:!1,children:Object.entries(e).map((([l,r])=>{const i=g[l]||l;return(0,u.jsxs)("div",{children:[(0,u.jsx)(t.CheckboxControl,{label:i,checked:r.enabled,onChange:t=>o({...e,[l]:{...r,enabled:t}})}),h.includes(l)&&r.enabled&&(0,u.jsx)(t.TextControl,{label:(0,n.__)(`${i} Value`,"checkout-fields-for-blocks"),value:r.value||"",onChange:t=>o({...e,[l]:{...r,value:t}})})]},l)}))}),A=({display:e,onChange:o})=>(0,u.jsxs)(t.PanelBody,{title:(0,n.__)("Display on","checkout-fields-for-blocks"),initialOpen:!1,children:[(0,u.jsx)(t.ToggleControl,{label:(0,n.__)("Order confirmation","checkout-fields-for-blocks"),checked:e.orderConfirmation,onChange:t=>o({...e,orderConfirmation:t})}),(0,u.jsx)(t.ToggleControl,{label:(0,n.__)("Admin order","checkout-fields-for-blocks"),checked:e.orderAdmin,onChange:t=>o({...e,orderAdmin:t})}),(0,u.jsx)(t.ToggleControl,{label:(0,n.__)("My Account - order","checkout-fields-for-blocks"),checked:e.orderMyAccount,onChange:t=>o({...e,orderMyAccount:t})}),(0,u.jsx)(t.ToggleControl,{label:(0,n.__)("Order e-mail","checkout-fields-for-blocks"),checked:e.orderEmail,onChange:t=>o({...e,orderEmail:t})})]}),p=JSON.parse('{"$schema":"https://schemas.wp.org/trunk/block.json","apiVersion":3,"name":"checkout-fields-for-blocks/input-url","version":"0.1.0","title":"Url","description":"","category":"checkout-fields-for-blocks","supports":{"html":true},"keywords":["checkout"],"parent":["woocommerce/checkout-totals-block","woocommerce/checkout-fields-block","woocommerce/checkout-contact-information-block","woocommerce/checkout-shipping-address-block","woocommerce/checkout-billing-address-block","woocommerce/checkout-shipping-method-block","woocommerce/checkout-shipping-methods-block","woocommerce/checkout-pickup-options-block"],"attributes":{"fieldId":{"type":"string","default":""},"fieldName":{"type":"string","default":""},"metaName":{"type":"string","default":""},"parentBlock":{"type":"string","default":""},"label":{"type":"string","default":""},"defaultValue":{"type":"string","default":""},"helpText":{"type":"string","default":""},"className":{"type":"string","default":""},"validationSettings":{"type":"object","default":{"required":{"enabled":false},"minLength":{"enabled":false,"value":""},"maxLength":{"enabled":false,"value":""},"pattern":{"enabled":false,"value":""}}},"display":{"type":"object","default":{"orderConfirmation":"","orderAdmin":"","orderMyAccount":"","orderEmail":""}}},"textdomain":"checkout-fields-for-blocks"}');(0,l.registerBlockType)(p,{icon:{src:(0,u.jsxs)(t.SVG,{version:"1.2",xmlns:"http://www.w3.org/2000/svg",viewBox:"0 0 74 74",width:"70",height:"70",children:[(0,u.jsx)("defs",{children:(0,u.jsx)("image",{width:"71",height:"58",id:"img1",href:"data:image/png;base64,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"})}),(0,u.jsx)("style",{}),(0,u.jsx)("use",{href:"#img1",x:"1",y:"7"})]})},edit:({attributes:e,setAttributes:o,clientId:l})=>{const{fieldId:s,label:d,defaultValue:g,helpText:h,inputType:p,validationSettings:b,display:m}=e,x=(e=>(0,c.useSelect)((o=>{const{getBlockRootClientId:t,getBlockName:l}=o("core/block-editor"),r=t(e);return r?l(r):null}),[e]))(l),w=(0,r.useBlockProps)();return(0,a.useEffect)((()=>{s||(o({fieldId:l}),o({display:{orderConfirmation:!0,orderAdmin:!0,orderMyAccount:!0,orderEmail:!0}})),o({parentBlock:x})}),[l,s,x,p,o]),(0,u.jsxs)(u.Fragment,{children:[(0,u.jsxs)(r.InspectorControls,{children:[(0,u.jsxs)(f,{attributes:e,setAttributes:o,children:[(0,u.jsx)(t.TextControl,{label:(0,n.__)("Label","checkout-fields-for-blocks"),value:d,onChange:e=>o({label:e})}),(0,u.jsx)(t.TextareaControl,{label:(0,n.__)("Help text","checkout-fields-for-blocks"),value:h,onChange:e=>o({helpText:e})})]}),(0,u.jsx)(k,{validationSettings:b,setValidationSettings:e=>o({validationSettings:e})}),(0,u.jsx)(A,{display:m,onChange:e=>o({display:e})})]}),(0,u.jsx)("div",{...w,children:(0,u.jsx)(i.ValidatedTextInput,{id:s,type:p,required:!1,label:d,value:g,onChange:e=>o({defaultValue:e})})})]})},save:({attributes:e})=>{const{text:o}=e;return(0,u.jsx)("div",{...r.useBlockProps.save(),children:(0,u.jsx)(r.RichText.Content,{value:o})})}})})();