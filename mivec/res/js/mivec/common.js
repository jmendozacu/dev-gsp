function toObject(queryString) {
	var a = queryString.split("&");
	var o = {};
	var t,nm;
	a.each(function(item,index){
		t = item.split("=");
		nm = t[0].substr(0,3)=='ff_' ? t[0].substr(3) : t[0];
		o[nm] = decodeURIComponent(t[1]);
	});
	return o;
}

function complex(divid_name,maxnum,nowid){
	for(var i=1;i<=maxnum;i++){
		document.getElementById(divid_name +"_"+i).style.display="none";
	}

	for(var i=1;i<=maxnum;i++){
		document.getElementById(divid_name +"_class_"+i).className="default";
	}

	document.getElementById(divid_name+"_class_"+nowid).className="active";
	document.getElementById(divid_name+"_"+nowid).style.display="block";
}

function ajax_start(){
	//document.body.style.filter = "Alpha(Opacity=60)";
	var div = new Element("div");
		div.setStyles({
			"id":"flowdiv",
			"height":"100%",
			"width":"100%",
			"left":0,
			"top":0,
			"position":"relative",
			"background-color":"#666",
			"filter":"Alpha(Opacity=60)"
		});
	div.injectInside($(document.body));
	//document.body.style.background = "#666";
}

function ajax_end(){
	document.body.style.filter = "";
	document.body.style.background = "";
}

function checkAll(e, itemName){
	var aa = document.getElementsByName(itemName);
	for (var i=0; i<aa.length; i++)
	aa[i].checked = e.checked;
}

function checkItem(e, allName){
	var all = document.getElementsByName(allName)[0];
	if(!e.checked) all.checked = false;
	else{
		var aa = document.getElementsByName(e.name);
		for (var i=0; i<aa.length; i++)
		if(!aa[i].checked) return;
		all.checked = true;
	}
}

function getCode(VerObj){
	VerObj.setStyle("visibility","hidden");
	VerObj.src = "";
	VerObj.setStyle("visibility","visible");
	VerObj.src = "/php/verifycode.php?" + Math.random();
}

function getMousePos(ev) {
	if(ev.pageX || ev.pageY){
	    return {x:ev.pageX, y:ev.pageY};
	}
	return {
	    x:ev.clientX + document.body.scrollLeft - document.body.clientLeft,
	    y:ev.clientY + document.body.scrollTop - document.body.clientTop
	  };
}

function getAbsoluteLeft( ob ){    
	if(!ob){return null;}    
		var obj = ob;    
		var objLeft = obj .offsetLeft;    
		while( obj != null && obj .offsetParent != null && obj .offsetParent.tagName != "BODY" ){    
		objLeft += obj .offsetParent.offsetLeft;    
		obj = obj .offsetParent;    
	}    
	return objLeft ;
}    
   
function getAbsoluteTop( ob ){    
	if(!ob){return null;}    
		var obj = ob;    
		var objTop = obj .offsetTop;    
		while( obj != null && obj .offsetParent != null && obj .offsetParent.tagName != "BODY" ){    
		objTop += obj .offsetParent.offsetTop;    
		obj = obj .offsetParent;    
	}    
	return objTop ;    
}   

//var errorImg = "<img src='"+ path +"/public/images/aicon/check_error.gif' > ";
var errorImg = "<img src='/js/mivec/res/images/aicon/drop-no.gif' > ";
var rightImg = "<img src='/js/mivec/res/images/aicon/drop-yes.gif' > ";

function checkIntVal(val) {
	//var pattern = /^([0-9]+[\.]?\d+)$/;
	var pattern = /^(\d+[\.]?\d+)$/
	return pattern.test(val);
}

function checkChinese(str) {
	return /.*[\u4e00-\u9fa5]+.*$/.test(str);
}

function checkCardid(val){
	var pattern = /^([0-9]{15}|[0-9]{18})$/;
	return pattern.test(val);
}

function checkMail(value) {
	var pattern = /^([a-zA-Z0-9._-])+@([a-zA-Z0-9_-])+(\.[a-zA-Z0-9_-])+/;
	return pattern.test(value);
}

function checkIsImg(value){
	var pattern = /(\.[jpg|jpeg|gif|png]+)$/;
	return pattern.test(value);
}

// 获取编辑器中HTML内容
function getEditorHTMLContents(EditorName) { 
    var oEditor = FCKeditorAPI.GetInstance(EditorName); 
    return(oEditor.GetXHTML(true)); 
}

// 获取编辑器中文字内容
function getEditorTextContents(EditorName) { 
    var oEditor = FCKeditorAPI.GetInstance(EditorName); 
    return(oEditor.EditorDocument.body.innerText); 
}

// 设置编辑器中内容
function SetEditorContents(EditorName, ContentStr) { 
    var oEditor = FCKeditorAPI.GetInstance(EditorName) ; 
    oEditor.SetHTML(ContentStr) ; 
}