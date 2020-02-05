<?php  
####### By Chel : Dated 15-02-2018 #######
require_once __DIR__ . "/vendor/autoload.php";
use EddTurtle\DirectUpload\Signature;
$upload = new Signature(
    "AKIAJTWIFZKPJAX5AMSQ",
    "E+75RAeG+XZS34gHJPA/BNr+vE65oZwXJTV8Kf2w",
    "oupbucket",
    "ap-south-1"
);

include(BASE_PATH.'meta_script_link.php'); 
include(BASE_PATH.'const_link.php');
$ip = ( $_SERVER['HTTP_HOST'] != '::1' ? $_SERVER['HTTP_HOST'] : '127.0.0.1' );

if(isset($_REQUEST['job_id']) && $_REQUEST['job_id'] != "")
{
$jobDetailsQuery = "select * from job j left join job_info ji on j.JOB_ID=ji.JOB_ID where j.JOB_ID = ".$_REQUEST['job_id']."";
$jobDetailsExec =  $mysqli->query($jobDetailsQuery);
$jobDetails = $jobDetailsExec->fetch_array(MYSQLI_ASSOC);
}

?>

<head>
<style>
.handprogress {
  height: 1.6em;
  width: 50%;
  background-color: #c9c9c9;
  position: relative;
}
.handprogress:before  {
  content: attr(data-label);
  font-size: 0.8em;
  position: absolute;
  text-align: center;
  top: 5px;
  left: 0;
  right: 0;
}
.handprogress .value {
  background-color: #4E9ACF !important;
  display: inline-block;
  height: 100%;
}

.passprogress {
  height: 1.6em;
  width: 50%;
  background-color: #c9c9c9;
  position: relative;
}
.passprogress:before  {
  content: attr(data-label);
  font-size: 0.8em;
  position: absolute;
  text-align: center;
  top: 5px;
  left: 0;
  right: 0;
}
.passprogress .value {
  background-color: #4E9ACF !important;
  display: inline-block;
  height: 100%;
}

.progress {
  height: 1.6em;
  width: 50%;
  background-color: #c9c9c9;
  position: relative;
}
.progress:before  {
  content: attr(data-label);
  font-size: 0.8em;
  position: absolute;
  text-align: center;
  top: 5px;
  left: 0;
  right: 0;
}
.progress .value {
  background-color: #4E9ACF !important;
  display: inline-block;
  height: 100%;
}

</style>
<link rel="stylesheet" href="css/awsstyle.css">

<script type="text/javascript">
var handoverAPIStatus;

					
					
$(document).ready(function(){
	var globalcount = 0;
	$('#titlecreation').on('submit',function(){
		var ipaddress = $('#ip').val();
		var error = 0;
		var condition = ["project_type", "acronym", "title",'Author',"workflow","Print","title"];
		$("#title").css({
				'border': '1px solid red !important',
				"background": "red !important"
				});
		$('#titlecreation input[type^=text],#titlecreation input[type^=number], #titlecreation select, #titlecreation textarea').each(function(){
			var id = $(this).attr('id');
			var value = $('#'+id).val();
			if( $.inArray(id,condition) !== -1 ){
			if(value == "")
			{
				error = 1;
				$("#"+id).css({
				'border': '1px dashed #FF3F3F',
				"background": "#FAEBE7"
				});	
			}
			else
			{
				$("#"+id).css({
				'border': '1px solid #D5D5D5',
				"background": "#FFFFFF"
				});
			}
			}
		});
		$('#titlecreation #handoverfile,#titlefile').each(function(){
			var id = $(this).attr('id');
			var value = $('#'+id).val();
			if(value == "")
			{
				error = 1;
				$('#'+id).css({
				'border': '1px dashed #FF3F3F',
				"background": "#FAEBE7"
				});	
			}
			else
			{
				$('#'+id).css({
				'border': '1px solid #D5D5D5',
				"background": "#FFFFFF"
				});
			}
		});
		
		if(error == 1)
		{
			$.notify("Madatory field(s) should not be empty..!!", {autoHide: true,className: 'error',globalPosition: 'top right', autoHideDelay: 3000});
			return false;
		}
		
		var isbn = $('#Print').val();
		if(isbn.length < 10 || (isbn.length > 10 && isbn.length < 13) ){
			$.notify("ISBN length is not correct..!!", {autoHide: true,className: 'error',globalPosition: 'top right', autoHideDelay: 3000});
			return false;
		}
		
		var acronym = $('#acronym').val();
		$('#acronym').val(acronym.toUpperCase());
		if(/^[a-zA-Z0-9_]*$/.test(acronym) == false){
			$('#acronym').notify('Special character(s) & space not allowed except "_"', {autoHide: true,className: 'error',elementPosition: 'bottom left', autoHideDelay: 3000});
			return false;
		}
		
		if($('#Manfilecheck').val() ==0 || $('#Manfilecheck').val() ==''){
			$.notify("Upload the manuscript file first and then click submit..!", {autoHide: true,className: 'error',globalPosition: 'top right', autoHideDelay: 3000});
			return false;
		}
		// var formdata = $('#titlecreation').serialize();
		// $.blockUI({ message: '<h1><img src="images/preloader.gif" /> This may take some times. Please bare while in-progress.... </h1>' });
		$.ajax({
			// url:'title_creationAjax_test.php',
			url:'title_creationAjax.php',
			type:'post',
			data: new FormData(this),
			processData: false,
			contentType: false,
			beforeSend:function(){
				$.blockUI({ message: '<h3><img src="images/preloader.gif" /> <div>Please wait while creating Title....</div></h3>' });
			},
			complete:function(){
				// $.unblockUI();
			},
			success:function(data){
				$.unblockUI();
				var status = JSON.parse(data);
				$.unblockUI();
				if(status.status == 'Success')
				{
					alfFunction(status);
					$.notify(status.message, {autoHide: true,className: 'success',globalPosition: 'top right', autoHideDelay: 4000});
					setTimeout(function() { 
					window.parent.location.href = 'title_creation_handoverform.php?job_id='+status.job_id 
					window.close();
					}, 4000);
				}
				else
				{
					$.notify(status.message, {autoHide: true,className: 'error',globalPosition: 'top right', autoHideDelay: 4000});
				}
				
			}
		});
		return false;
	});
	
	// Function Declarattion
	commonFunction = {
		
		//email check function
		emailCheck: function(id,value) {
			var filter = /^([\w-\.]+)@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.)|(([\w-]+\.)+))([a-zA-Z]{2,4}|[0-9]{1,3})(\]?)$/;
			if (filter.test(value)) {
				return true;
			}
			else {
				$("#"+id).notify("Incorrect Email Id..!!", {autoHide: true,className: 'error',globalPosition: 'top center', autoHideDelay: 3000});
				return false;
			}
		},
		
		//isbn check
		isbnCheck: function(id,value){
			var length = value.length;
			if ( length > 15) {
				$("#"+id).notify("ISBN cannot be more than 15 characters..!!", {autoHide: true,className: 'error',globalPosition: 'top center', autoHideDelay: 5000});
				return false;
			}
			else {
				return true;
			}
		}
		
	}
	
	$('#ok').click(function(){
		$.confirm({
		title: 'Closing window..',
		content: 'Are you sure ?',
		type: 'blue',
		boxWidth: '30%',
		useBootstrap: false,
		typeAnimated: true,
		buttons: {
		tryAgain: {
			text: 'Ok',
			btnClass: 'btn-blue',
			action: function(){
				// window.parent.location.href = 'dashboard.php';
				window.close();	
			}
		},
		cancel: function () {
		}
		}
		});
	});
	
	$('#linkfile').click(function(){
		var ipaddress = $('#ip').val();	
		var job_id = $('#job_id').val();	
		var https = $('#https').val();	
		var myWindow = window.open(https+'://'+ipaddress+"/magnus_oupbook/fileupload.php?job_id="+job_id, "_blank", "width=800,height=600");	
	});
	
	$('#Author').keyup(function(){
		var authorname = $('#Author').val();
		var author = "";
		var authorarr = authorname.split(' ');
		if(authorarr.length > 1){
		// if(authorarr[0].indexOf(',') != -1){
			// author = authorarr[0].replace(',','');
		// }else{
			// author = authorarr[1];
		// }
			if(authorarr[0].substr(-1) == ','){
				author = authorarr[0].replace(',','');
			}else if(authorarr[0].substr(-1) == '.'){
				author = authorarr[0].replace('.','');
				if(author.length == 1){
				author = authorarr[1];
				}
			}else{
				author = authorarr[authorarr.length-1];
			}
		}else{
			author = authorname;
		}
		var isbn = $('#Print').val();
		if(author == "" || isbn == ""){
			underscore = "";
		}else{
			underscore = "_";
		}
		var acronym = author.toUpperCase()+underscore+isbn.toUpperCase();
		$('#acronym').val(acronym);
	});
	
	$('#Author').keydown(function(e){
		var k = e.keyCode;
		// alert(k);
        return ((k >= 65 && k <= 90) || k == 8 || k == 46 || k == 9 || k == 190 || k == 32 || k == 188 || (k >= 37 && k <= 40));
	});
	
	$('#Print').keyup(function(e){
		var authorname = $('#Author').val();
		var isbn = $('#Print').val();
		if(isbn.length <= 13){
			var author = "";
			var authorarr = authorname.split(' ');
			if(authorarr.length > 1){
				// if(authorarr[0].indexOf(',') != -1){
					// author = authorarr[0].replace(',','');
				// }else{
					// author = authorarr[1];
				// }
				if(authorarr[0].substr(-1) == ','){
					author = authorarr[0].replace(',','');
				}else if(authorarr[0].substr(-1) == '.'){
					author = authorarr[0].replace('.','');
					if(author.length == 1){
					author = authorarr[1];
					}
				}else{
					author = authorarr[1];
				}
			}else{
				author = authorname;
			}
			var isbn = $('#Print').val();
			if(author == "" || isbn == ""){
				underscore = "";
			}else{
				underscore = "_";
			}
			var acronym = author.toUpperCase()+underscore+isbn.toUpperCase();
			$('#acronym').val(acronym);
		}
	});
	
	$('#Print').keydown(function(e) {
		var isbn = $('#Print').val();
		var pattern = /^[0-9xX]+$/;
		if(pattern.test(isbn)){
			if(isbn.length >= 13 && e.key != 'Backspace' && e.key != 'Delete'){
			e.preventDefault();
			return false;
			}
		}else{
			if(e.key != 'Backspace' && e.key != 'Delete' && isbn.length > 1){
			e.preventDefault();
			return false;
			}	
		}
	});
	
	function Elementchange(type,btn){
		if(type 	==	"add"){
			$("#"+btn).attr('disabled','disabled');
			$("#"+btn).css({'background':'#dddddd','cursor':'none'});
			$("#"+btn).val("In progress");
		}else if(type 	==	"completed"){
			$("#"+btn).attr('disabled','disabled');
			$("#"+btn).css({'background':'#dddddd','cursor':'none'});
			$("#"+btn).val("Completed");
		}
		else{
			$("#"+btn).removeAttr('disabled');
			$("#"+btn).css({'background':'#4E9ACF','cursor':'pointer'});
			$("#"+btn).val("Upload");
		}
	}
	
						
	
	$(".FileUpload").on("click",function() {
		var getcurrentuploadFile 	=	$(this).attr('data-nameofthefile');
		if(getcurrentuploadFile 	==	"" || getcurrentuploadFile 	==	undefined){
			$.notify("Invalid data format", {autoHide: true,className: 'error',globalPosition: 'top right', autoHideDelay: 3000});
			return false;
		}
		var acronym 	= 	$('#acronym').val();
		var file_data 	=	"";
		var progressBar =	"";
		var fileCheck 	=	"";
		var fileDir 	=	"";
		var urlLinkName	=	"";
		if(getcurrentuploadFile 	==	"manuscriptFile"){
			urlLinkName 	=	"manFileUpload";
			file_data 		= 	$('#titlefile').prop('files')[0];
			if(file_data 	==	undefined || file_data 	==	""){
				$(".titlehand").css({
				'border': '1px dashed #FF3F3F',
				"background": "#FAEBE7"
				});
				return false;
			}else{
				$(".titlehand").css({
				'border': '1px solid #D5D5D5',
				"background": "#FFFFFF"
				});
			}
		}else if(getcurrentuploadFile 	==	"handoverfile"){
			urlLinkName 	=	"handoverFileUpload";
			file_data 		= 	$('#handoverfile').prop('files')[0];
			if(file_data 	==	undefined || file_data 	==	""){
				$(".handtitle").css({
				'border': '1px dashed #FF3F3F',
				"background": "#FAEBE7"
				});
				return false;
			}else{
				$(".handtitle").css({
				'border': '1px solid #D5D5D5',
				"background": "#FFFFFF"
				});
			}
	
		}else{
			urlLinkName 	=	"passFileUpload";
			file_data 		= 	$('#passportfile').prop('files')[0];
			if(file_data 	==	undefined || file_data 	==	""){
				$(".passtitle").css({
				'border': '1px dashed #FF3F3F',
				"background": "#FAEBE7"
				});
				return false;
			}else{
				$(".passtitle").css({
				'border': '1px solid #D5D5D5',
				"background": "#FFFFFF"
				});
			}
		}
		
		
		var fname = file_data.name;
		var fext = fname.substring(fname.lastIndexOf('.')+1);
		var fsize = file_data.size;
		if(getcurrentuploadFile 	==	"manuscriptFile"){
			$('#filename').val(fname+'^'+fext+'^'+fsize);
			progressBar	=	"progress";
			fileCheck	=	"Manfilecheck";
			fileDir		=	"upDir";
		}else if(getcurrentuploadFile 	==	"handoverfile"){
			$('#handfilename').val(fname+'^'+fext+'^'+fsize);
			if(fext 	!=	"docx" && fext 	!=	"doc"){
				$.notify("Invalid file format", {autoHide: true,className: 'error',globalPosition: 'top right', autoHideDelay: 3000});
				return false;
			}
			progressBar	=	"handprogress";
			fileCheck	=	"Handfilecheck";
			fileDir		=	"HandDir";			
		}else{
			if(fext 	!==	"docx" && fext 	!==	"doc"){
				$.notify("Invalid file format", {autoHide: true,className: 'error',globalPosition: 'top right', autoHideDelay: 3000});
				return false;
			}
			$('#passfilename').val(fname+'^'+fext+'^'+fsize);	
			progressBar	=	"passprogress";
			fileCheck	=	"Passfilecheck";
			fileDir		=	"PassDir";
		}
		var form_data = new FormData();
		form_data.append('file', file_data);
		/*
		var form_data 	= 	new FormData($('input[name^="handoverFileUpload"]'));
		// form_data.append('file', file_data);
		jQuery.each($('input[name^="handoverFileUpload"]')[0].files, function(i, file) {
			form_data.append(i,file);
		});*/
		
		if(acronym == ""){
			$("#acronym").css({
			'border': '1px dashed #FF3F3F',
			"background": "#FAEBE7"
			});
			return false;
		}
		
		// if($('#'+getcurrentuploadFile).val() == ''){
			// $(".handtitle").css({
			// 'border': '1px dashed #FF3F3F',
			// "background": "#FAEBE7"
			// });
			// return false;
		// }
		
		var isbn = $('#Print').val();
		if(isbn.length < 10 || (isbn.length > 10 && isbn.length < 13) ){
			$.notify("ISBN length is not correct..!!", {autoHide: true,className: 'error',globalPosition: 'top right', autoHideDelay: 3000});
			return false;
		}
		
		if($('#Author').val() == ''){
			$("#Author").css({
			'border': '1px dashed #FF3F3F',
			"background": "#FAEBE7"
			});
			return false;
		}
		
		//change element style inprogress and undo old
		Elementchange('add',urlLinkName);
		
		$.ajax({
			url: 'title_creationAjax.php?type='+urlLinkName+'&acronym='+acronym, 
			dataType: 'text',
			cache: false,
			contentType: false,
			processData: false,
			xhr: function() {
				var myXhr = $.ajaxSettings.xhr();
				if (myXhr.upload) {
					// For handling the progress of the upload
					myXhr.upload.addEventListener('progress', function(e) {
					if (e.lengthComputable) {
						$('.'+progressBar).attr("style","margin-top: 1%;display:block;font-size: 13px;color: black;font-weight: bold;width:50%;");
						$('.'+progressBar).find(".value").attr("style","width:3% !important");
							$('.'+progressBar).attr({
								value: e.loaded,
								max: e.total,
								"data-label":"3%"
							});
						var percentComplete = parseInt((e.loaded / e.total) * 100);
						if(percentComplete!="100"){
							$('.'+progressBar).find(".value").attr("style","width:"+percentComplete+"% !important");
							$('.'+progressBar).attr({
								value: e.loaded,
								max: e.total,
								"data-label":percentComplete+"% complete",
							});
						}
					}
					} , false);
				}
				return myXhr;
			},
			data: form_data,
			type: 'post',
			method: "POST",
			mimeType: "multipart/form-data",
            async: true,
			crossDomain: true,
			dataType: "json",
			success: function (response) {
				if(response.status == 1){
					//change element style inprogress and undo old
					Elementchange('completed',urlLinkName);
					$('.'+progressBar).attr({
						"data-label":"100% complete",
					});
					$('.'+progressBar).find(".value").attr("style","width:100% !important");
					$.notify(response.message, {autoHide: true,className: 'success',globalPosition: 'top right', autoHideDelay: 3000});
					$("#"+fileCheck).val('1');
					var folder = $("#acronym").val();
					$("#"+fileDir).val(folder);
				}else{
					//change element style inprogress and undo old
					Elementchange('remove',urlLinkName);
					$('.'+progressBar).attr({
						"data-label":"Failed",
					});
					$('.'+progressBar).find(".value").attr("style","width:100% !important");
					$.notify(response.message, {autoHide: true,className: 'error',globalPosition: 'top right', autoHideDelay: 3000});
					//check any error while file uploading
					if(response.failedFile.length != 0){
						$.each(response.failedFile,function(index,item){
							$.notify(item, {autoHide: true,className: 'error',globalPosition: 'top right', autoHideDelay: 3000});	
						});
					}
					$("#"+fileCheck).val('0');
					$("#"+fileDir).val('');
				}
			}
		});
	});
	
	/*$("#handoverFileUpload").on("click",function() {
		var acronym 	= 	$('#acronym').val();
		var file_data 	= 	$('#handoverfile').prop('files')[0];
		if(file_data 	==	undefined || file_data 	==	""){
			$(".handtitle").css({
			'border': '1px dashed #FF3F3F',
			"background": "#FAEBE7"
			});
			return false;
		}
		var fname = file_data.name;
		var fext = fname.substring(fname.lastIndexOf('.')+1);
		var fsize = file_data.size;
		$('#filename').val(fname+'^'+fext+'^'+fsize);
		var form_data = new FormData();
		form_data.append('file', file_data);
		
		var form_data 	= 	new FormData($('input[name^="handoverFileUpload"]'));
		// form_data.append('file', file_data);
		jQuery.each($('input[name^="handoverFileUpload"]')[0].files, function(i, file) {
			form_data.append(i,file);
		});
		
		if(acronym == ""){
			$("#acronym").css({
			'border': '1px dashed #FF3F3F',
			"background": "#FAEBE7"
			});
			return false;
		}
		
		if($('#handoverfile').val() == ''){
			$(".handtitle").css({
			'border': '1px dashed #FF3F3F',
			"background": "#FAEBE7"
			});
			return false;
		}
		
		if($('#Author').val() == ''){
			$(".handtitle").css({
			'border': '1px dashed #FF3F3F',
			"background": "#FAEBE7"
			});
			return false;
		}
		
		var isbn = $('#Print').val();
		if(isbn.length < 10 || (isbn.length > 10 && isbn.length < 13) ){
			$.notify("ISBN length is not correct..!!", {autoHide: true,className: 'error',globalPosition: 'top right', autoHideDelay: 3000});
			return false;
		}
	
		$.ajax({
			url: 'title_creationAjax.php?type=handoverFileUpload&acronym='+acronym, 
			dataType: 'text',
			cache: false,
			contentType: false,
			processData: false,
			xhr: function() {
				var myXhr = $.ajaxSettings.xhr();
				if (myXhr.upload) {
					// For handling the progress of the upload
					myXhr.upload.addEventListener('handprogress', function(e) {
					if (e.lengthComputable) {
						$('.handprogress').attr("style","margin-top: 1%;display:block;font-size: 13px;color: black;font-weight: bold;width:50%;");
						var percentComplete = parseInt((e.loaded / e.total) * 100);
						if(percentComplete!="100"){
							alert(percentComplete);
							$('.handprogress').find(".value").attr("style","width:"+percentComplete+"% !important");
							$('.handprogress').attr({
								value: e.loaded,
								max: e.total,
								"data-label":percentComplete+"% complete",
							});
						}
					}
					} , false);
				}
				return myXhr;
			},
			data: form_data,
			type: 'post',
			method: "POST",
			mimeType: "multipart/form-data",
            async: true,
			crossDomain: true,
			dataType: "json",
			success: function (response) {
				if(response.status == 1){
					$('.handprogress').attr({
						"data-label":"100% complete",
					});
					$('.handprogress').find(".value").attr("style","width:100% !important");
					$.notify(response.message, {autoHide: true,className: 'success',globalPosition: 'top right', autoHideDelay: 3000});
					$("#Handfilecheck").val('1');
					var folder = $("#acronym").val();
					$("#HandDir").val(folder);
				}else{
					$('.handprogress').attr({
						"data-label":"Failed",
					});
					$('.handprogress').find(".value").attr("style","width:100% !important");
					$.notify(response.message, {autoHide: true,className: 'error',globalPosition: 'top right', autoHideDelay: 3000});
					//check any error while file uploading
					if(response.failedFile.length != 0){
						$.each(response.failedFile,function(index,item){
							$.notify(item, {autoHide: true,className: 'error',globalPosition: 'top right', autoHideDelay: 3000});	
						});
					}
					$("#Handfilecheck").val('0');
					$("#HandDir").val('');
				}
			}
		});
	});
	
	$("#passFileUpload").on("click",function() {
		var acronym 	= 	$('#acronym').val();
		var file_data 	= 	$('#passportfile').prop('files')[0];
		if(file_data 	==	undefined || file_data 	==	""){
			$(".passtitle").css({
			'border': '1px dashed #FF3F3F',
			"background": "#FAEBE7"
			});
			return false;
		}
		var fname = file_data.name;
		var fext = fname.substring(fname.lastIndexOf('.')+1);
		var fsize = file_data.size;
		$('#filename').val(fname+'^'+fext+'^'+fsize);
		var form_data = new FormData();
		form_data.append('file', file_data);
		
		var form_data 	= 	new FormData($('input[name^="passFileUpload"]'));
		// form_data.append('file', file_data);
		jQuery.each($('input[name^="passFileUpload"]')[0].files, function(i, file) {
			form_data.append(i,file);
		});
		
		if(acronym == ""){
			$("#acronym").css({
			'border': '1px dashed #FF3F3F',
			"background": "#FAEBE7"
			});
			return false;
		}
		
		if($('#passportfile').val() == ''){
			$(".passtitle").css({
			'border': '1px dashed #FF3F3F',
			"background": "#FAEBE7"
			});
			return false;
		}
		
		if($('#Author').val() == ''){
			$(".passtitle").css({
			'border': '1px dashed #FF3F3F',
			"background": "#FAEBE7"
			});
			return false;
		}
		
		var isbn = $('#Print').val();
		if(isbn.length < 10 || (isbn.length > 10 && isbn.length < 13) ){
			$.notify("ISBN length is not correct..!!", {autoHide: true,className: 'error',globalPosition: 'top right', autoHideDelay: 3000});
			return false;
		}
	
		$.ajax({
			url: 'title_creationAjax.php?type=passFileUpload&acronym='+acronym, 
			dataType: 'text',
			cache: false,
			contentType: false,
			processData: false,
			xhr: function() {
				var myXhr = $.ajaxSettings.xhr();
				if (myXhr.upload) {
					// For handling the progress of the upload
					myXhr.upload.addEventListener('passprogress', function(e) {
					if (e.lengthComputable) {
						$('.passprogress').attr("style","margin-top: 1%;display:block;font-size: 13px;color: black;font-weight: bold;width:50%;");
						var percentComplete = parseInt((e.loaded / e.total) * 100);
						if(percentComplete!="100"){
							$('.passprogress').find(".value").attr("style","width:"+percentComplete+"% !important");
							$('.passprogress').attr({
								value: e.loaded,
								max: e.total,
								"data-label":percentComplete+"% complete",
							});
						}
					}
					} , false);
				}
				return myXhr;
			},
			data: form_data,
			type: 'post',
			method: "POST",
			mimeType: "multipart/form-data",
            async: true,
			crossDomain: true,
			dataType: "json",
			success: function (response) {
				if(response.status == 1){
					$('.passprogress').attr({
						"data-label":"100% complete",
					});
					$('.passprogress').find(".value").attr("style","width:100% !important");
					$.notify(response.message, {autoHide: true,className: 'success',globalPosition: 'top right', autoHideDelay: 3000});
					$("#Passfilecheck").val('1');
					var folder = $("#acronym").val();
					$("#PassDir").val(folder);
				}else{
					$('.passprogress').attr({
						"data-label":"Failed",
					});
					$('.passprogress').find(".value").attr("style","width:100% !important");
					$.notify(response.message, {autoHide: true,className: 'error',globalPosition: 'top right', autoHideDelay: 3000});
					//check any error while file uploading
					if(response.failedFile.length != 0){
						$.each(response.failedFile,function(index,item){
							$.notify(item, {autoHide: true,className: 'error',globalPosition: 'top right', autoHideDelay: 3000});	
						});
					}
					$("#Passfilecheck").val('0');
					$("#PassDir").val('');
				}
			}
		});
	});
		
	$("#manFileUpload").on("click",function() {
		var acronym 	= 	$('#acronym').val();
		var file_data = $('#titlefile').prop('files')[0];
		if(file_data 	==	undefined || file_data 	==	""){
			$(".titlehand").css({
			'border': '1px dashed #FF3F3F',
			"background": "#FAEBE7"
			});
			return false;
		}
		var fname = file_data.name;
		var fext = fname.substring(fname.lastIndexOf('.')+1);
		var fsize = file_data.size;
		$('#filename').val(fname+'^'+fext+'^'+fsize);
		var form_data = new FormData();
		form_data.append('file', file_data);
		
		var form_data 	= 	new FormData($('input[name^="manuscriptFile"]'));
		// form_data.append('file', file_data);
		jQuery.each($('input[name^="manuscriptFile"]')[0].files, function(i, file) {
			form_data.append(i,file);
		});
		
		if(acronym == ""){
			$("#acronym").css({
			'border': '1px dashed #FF3F3F',
			"background": "#FAEBE7"
			});
			return false;
		}
		
		if($('#titlefile').val() == ''){
			$(".titlehand").css({
			'border': '1px dashed #FF3F3F',
			"background": "#FAEBE7"
			});
			return false;
		}
		
		if($('#Author').val() == ''){
			$(".titlehand").css({
			'border': '1px dashed #FF3F3F',
			"background": "#FAEBE7"
			});
			return false;
		}
		
		var isbn = $('#Print').val();
		if(isbn.length < 10 || (isbn.length > 10 && isbn.length < 13) ){
			$.notify("ISBN length is not correct..!!", {autoHide: true,className: 'error',globalPosition: 'top right', autoHideDelay: 3000});
			return false;
		}
	
		$.ajax({
			// url: 'title_creationAjax_test.php?type=manFileUpload&acronym='+acronym, 
			url: 'title_creationAjax.php?type=manFileUpload&acronym='+acronym, 
			dataType: 'text',
			cache: false,
			contentType: false,
			processData: false,
			xhr: function() {
				var myXhr = $.ajaxSettings.xhr();
				if (myXhr.upload) {
					// For handling the progress of the upload
					myXhr.upload.addEventListener('progress', function(e) {
					if (e.lengthComputable) {
						$('.progress').attr("style","margin-top: 1%;display:block;font-size: 13px;color: black;font-weight: bold;width:50%;");
						var percentComplete = parseInt((e.loaded / e.total) * 100);
						if(percentComplete!="100"){
							$('.progress').find(".value").attr("style","width:"+percentComplete+"% !important");
							$('.progress').attr({
								value: e.loaded,
								max: e.total,
								"data-label":percentComplete+"% complete",
							});
						}
					}
					} , false);
				}
				return myXhr;
			},
			data: form_data,
			type: 'post',
			method: "POST",
			mimeType: "multipart/form-data",
            async: true,
			crossDomain: true,
			dataType: "json",
			success: function (response) {
				if(response.status == 1){
					$('.progress').attr({
						"data-label":"100% complete",
					});
					$('.progress').find(".value").attr("style","width:100% !important");
					$.notify(response.message, {autoHide: true,className: 'success',globalPosition: 'top right', autoHideDelay: 3000});
					$("#Manfilecheck").val('1');
					var folder = $("#acronym").val();
					$("#upDir").val(folder);
				}else{
					$('.progress').attr({
						"data-label":"Failed",
					});
					$('.progress').find(".value").attr("style","width:100% !important");
					$.notify(response.message, {autoHide: true,className: 'error',globalPosition: 'top right', autoHideDelay: 3000});
					//check any error while file uploading
					if(response.failedFile.length != 0){
						$.each(response.failedFile,function(index,item){
							$.notify(item, {autoHide: true,className: 'error',globalPosition: 'top right', autoHideDelay: 3000});	
						});
					}
					$("#Manfilecheck").val('0');
					$("#upDir").val('');
				}
			}
		});
	});*/
		
});

function titleEmailNotification(job_id)
{
	$.ajax({
		url:"title_creationAjax.php",
		type:'post',
		data:{req:'titleCreationEmail',job_id:job_id},
		success:function(data){
			console.log(data);
		}
	});
}


//###### Manuscript File Upload With Progress Bar ########//
function _(el) {
  return document.getElementById(el);
}

function uploadFile() {
	
	var acronym = $("#acronym").val();
	var file = _("titlefile").files[0];
	var file = _("handoverfile").files[0];
	
	var error = 0;
	var condition = ["project_type", "acronym", "title",'Author',"workflow","Print","title"];
	$("#title").css({
			'border': '1px solid red !important',
			"background": "red !important"
			});
	// $('#titlecreation input[type^=text],#titlecreation input[type^=number], #titlecreation select, #titlecreation textarea').each(function(){
	jQuery.each( condition, function( i, val ) {
  
		// var id = $(this).attr('id');
		var id = val;
		var value = $('#'+id).val();
		if( $.inArray(id,condition) !== -1 ){
		if(value == "")
		{
			error = 1;
			$("#"+id).css({
			'border': '1px dashed #FF3F3F',
			"background": "#FAEBE7"
			});	
		}
		else
		{
			$("#"+id).css({
			'border': '1px solid #D5D5D5',
			"background": "#FFFFFF"
			});
		}
		}
	});
	$('#titlecreation #handoverfile,#titlefile').each(function(){
		var id = $(this).attr('id');
		var value = $('#'+id).val();
		if(value == "")
		{
			error = 1;
			$('#'+id).css({
			'border': '1px dashed #FF3F3F',
			"background": "#FAEBE7"
			});	
		}
		else
		{
			$('#'+id).css({
			'border': '1px solid #D5D5D5',
			"background": "#FFFFFF"
			});
		}
	});
	if(error == 1)
	{
		$.notify("Madatory field(s) should not be empty..!!", {autoHide: true,className: 'error',globalPosition: 'top right', autoHideDelay: 3000});
		return false;
	}
	
	var isbn = $('#Print').val();
	if(isbn.length < 10 || (isbn.length > 10 && isbn.length < 13) ){
		$.notify("ISBN length is not correct..!!", {autoHide: true,className: 'error',globalPosition: 'top right', autoHideDelay: 3000});
		return false;
	}
	// alert(file.name+" | "+file.size+" | "+file.type);
	if(file.size > 1073741824){ //1GB
		alert('File size cannot be more than 1GB');
		return false;
	}
	var formdata = new FormData();
	formdata.append("handoverfile", file);
	formdata.append("titlefile", file);
	var ajax = new XMLHttpRequest();
	//ajax.upload.addEventListener("progress", progressHandler, false);
	ajax.addEventListener("load", completeHandler, false);
	ajax.addEventListener("error", errorHandler, false);
	ajax.addEventListener("abort", abortHandler, false);
	ajax.open("POST", "uploadtestAjax.php?acronym="+acronym);
	ajax.send(formdata);
}


function progressHandler(event) {
  // _("loaded_n_total").innerHTML = "Uploaded " + event.loaded + " bytes of " + event.total;
  var percent = (event.loaded / event.total) * 100;
  _("progressBar").value = Math.round(percent);
  // _("status").innerHTML = Math.round(percent) + "% uploaded... please wait";
}

function completeHandler(event) {
  // _("status").innerHTML = event.target.responseText;
  // alert(event.target.responseText);
  if(event.target.responseText == "success"){
	  $('#titlecreation').submit();
  }
  // _("progressBar").value = 0;
}

function errorHandler(event) {
  _("status").innerHTML = "Upload Failed";
}

function abortHandler(event) {
  _("status").innerHTML = "Upload Aborted";
}

function alfFunction(data){
	$.ajax({
		url:"title_creationAjax_test.php",
		type:'post',
		data:data,
		async:true,
		success:function(data){
			console.log(data);
		}
	});
}

</script>
<style type="text/css">
.spancolor{ color:red; }

.form-select{
   // margin-left: 7px;
    //width: 298px;
    display: block;
    width: 60%;
    height: 30px;
    //padding: 6px 12px;
    font-size: 12px;
    line-height: 1.42857143;
    color: #555;
    background-color: #fff;
    background-image: none;
    border: 1px solid #ccc;
    border-radius: 4px;
    -webkit-box-shadow: inset 0 1px 1px rgba(0,0,0,.075);
    box-shadow: inset 0 1px 1px rgba(0,0,0,.075);
    -webkit-transition: border-color ease-in-out .15s,-webkit-box-shadow ease-in-out .15s;
    -o-transition: border-color ease-in-out .15s,box-shadow ease-in-out .15s;
    transition: border-color ease-in-out .15s,box-shadow ease-in-out .15s;
}

.input{
	width: 60%;
    height: 30px;
}

.heading{
	background:#d6d6d6 !important;
}
.sub-heading{
	background:#ededed !important;
}

.txtarea{
	height:100%;
	width:60%;
}

.filerow{
	padding:5px;
}

.uploadfile
{
	width:50%;
	border:1px solid #c1c1c1;
	border-radius:5px;
	background-color:white;
}
</style>
<div class="main-container">
<?php //include(BASE_PATH.'header.php'); ?>
<div id="menu" class="mainm">
<div id="menubar" style="height:30px">
</div>
</div>
<div class="bread-crums_wrap">
<div class="bread-crums"><a href="#">Home</a> &raquo; Project Title Creation (Handover Form)</div>
</div>
<div class="msg_wrap">
	
</div>
<div class="dashlet-panel-full">    
<!--<form method="post" action="" name="titlecreation" id="titlecreation"enctype="multipart/form-data">-->


<table width="100%" border="0"  id="workflowtable" class="titletable">
	<tr>
		<td colspan="4" class="heading"><center><strong>Title Specs</strong></center></td>
	</tr>
	<tr>
		<td>Project:<span class="spancolor">*</span></td>
		<td width="35%">
		<select class="form-select" name="project_type" id="project_type">
		<option value="">Select Any</option>
		<?php
		$getProject = "select ID,NAME from project_type where SUB_CIRCLE_ID in (".$_SESSION['subcircle'].") and IS_ACTIVE=1";
		$execProject = $mysqli->query($getProject);
		while($project_type = $execProject->fetch_array(MYSQLI_ASSOC))
		{
		?>
		<option value="<?php echo $project_type['ID'] ?>" <?php if($jobDetails['PROJECT_TYPE'] == $project_type['ID']) { echo "selected"; } ?>><?php echo $project_type['NAME'] ?></option>
		<?php
		}
		?>
		</select>
		</td>
		<td >Acronym:<span class="spancolor">*</span></td>
		<td width="35%">
		<input  type="text" id="acronym"  autocomplete="off" name="acronym" class="input" placeholder="Auto generate Author_ISBN" value="<?php echo $jobDetails['ACRONYM'] ?>"/>
		</td>
	</tr>
	<tr>
		<td>Title:<span class="spancolor">*</span></td>
		<td width="35%">
		<textarea id="title"  autocomplete="off" name="title" class="txtarea"><?php echo mb_convert_encoding($jobDetails['JOB_TITLE'],"HTML-ENTITIES","UTF-8") ?></textarea>
		</td>
		<td>Author/Editor:<span class="spancolor">*</span></td>
		<td width="35%">
		<input  type="text" id="Author"  autocomplete="off" name="Author" class="input" value="<?php echo $jobDetails['AUTHOR_NAME'] ?>">
		</td>
	</tr>
	<tr>
		<td>Print ISBN:<span class="spancolor">*</span></td>
		<td width="35%">
		<input  type="text" id="Print"  autocomplete="off" name="Print" class="input" value="<?php echo $jobDetails['ISBN'] ?>">
		</td>
		<td>Instructions:</td>
		<td width="35%">
		<textarea name="instructions" id="instructions" class="txtarea" placeholder="100 Characters Max." maxlength="100"></textarea>
		</td>
	</tr>
	<?php if(isset($_REQUEST['job_id']) && $_REQUEST['job_id'] != ""){ ?>
	<!--<tr>
		<td>Link File(s) To Title</td>
		<td colspan="3">
		<a href="javascript:void(0)" id="linkfile" title="Click here to link file">Click here <img style="margin-bottom:-5px;max-width:20px" src="images/attachment.png"></a>
		</td>
	</tr>-->
	<?php }else{ ?>
	<tr>
		<td>Handover Form<span class="spancolor">*</span></td>
		<td colspan="3">
		<div class="uploadfile">
		<div class="filerow handtitle" id="filerow">
		<input type="hidden" id="HandDir" name="HandDir" value=""/>
		<input type="hidden" id="handfilename" name="handfilename" value=""/>
		<form enctype="multipart/form-data" action="<?php echo $upload->getFormUrl(); ?>"  method="post" class="handover-upload">
			<?php echo $upload->getFormInputsAsHtml(); ?>
			<input type="file" name="file" id="handoverfile">
		</form>
		</div>
		</div>
		<div class="handover-bar-area"></div>
		</td>
	</tr>
	<tr>
		<td>Title Passport<!--<span class="spancolor">*</span>--></td>
		<td colspan="3">
		<div class="uploadfile">
		<div class="filerow passtitle" id="filerow">
		<input type="hidden" id="PassDir" name="PassDir" value=""/>
		<input type="hidden" id="passfilename" name="passfilename" value=""/>
		<form enctype="multipart/form-data" action="<?php echo $upload->getFormUrl(); ?>"  method="post" class="passport-upload">
                <?php echo $upload->getFormInputsAsHtml(); ?>
				<input type="file" name="file" id="passportfile">
		</form>
		</div>
		</div>
		<div class="passport-bar-area"></div>
		<!--<input type="button" data-nameofthefile="passportfile" class="FileUpload" name="passFileUpload" id="passFileUpload" value="Upload" style="margin-top: -25px;margin-left: 52%;"/>
		<div class="passprogress" style="margin-top: 1%;display:none;font-size: 13px;color: black;font-weight: bold;">
			<span class="value"></span>
		</div>
		<input type="hidden" id="Passfilecheck" class="Manfilecheck" value="">-->
		</td>
	</tr>
	<tr>
		<td>Manuscript<span class="spancolor">*</span></td>
		<td colspan="3">
		<input type="hidden" id="filename" name="filename" value=""/>
		<input type="hidden" id="upDir" name="upDir" value=""/>
		<div class="uploadfile">
		<div class="filerow titlehand" id="filerow">
		<form enctype="multipart/form-data" action="<?php echo $upload->getFormUrl(); ?>"  method="post" class="manuscript-upload">
                <?php echo $upload->getFormInputsAsHtml(); ?>
				<input type="file" name="file" id="titlefile">
		</form>
		</div>
		</div>
		<div class="manuscript-bar-area"></div>
		</td>
	</tr>
	<?php } ?>
</table>
<table width="100%" border="0"  id="workflowtable">
<tr>
	<td colspan="4" align="center">
	<input type="hidden" name="ip" id="ip" value="<?php echo $ip ?>"/>
	<input type="hidden" name="https" id="https" value="<?php echo HTTPS ?>"/>
	<input type="hidden" name="title_creation_handoverform" id="title_creation_handoverform" value="1"/>
	<center>
	<?php if(isset($_REQUEST['job_id']) && $_REQUEST['job_id'] != ""){ ?>
	<input type="hidden" name="job_id" id="job_id" value="<?php echo $_REQUEST['job_id'] ?>">
	<input type="button" name="ok" id="ok" value="   Ok   "/>
	<?php }else{ ?>
	<!-- input type="button" name="title_create" id="title_create" onclick="return uploadFile();" value="Submit"/-->
	<input type="submit" name="title_create" id="title_create" value="Submit"/>
	<?php } ?>
	</center>
	</td>
</tr>
</table>

<script type="text/javascript" src="script/jquery-ui.min.js"></script>
<script type="text/javascript" src="script/aws.fileupload.min.js"></script>
<script>

	//handover file upload
	$(document).ready(function(){
		var form 	= 	$('.handover-upload');
		var filesUploaded = [];					
		var folders = 	[];
		form.fileupload({
			// url: "//s3-ap-south-1.amazonaws.com/oupbucket",
			url:"//oupbucket.s3-accelerate.amazonaws.com/",
			type: "post",
			datatype: 'xml',
			add: function (event, data) {
				var acronym = 	$('#acronym').val();
				if(acronym == ""){
					$("#acronym").css({
					'border': '1px dashed #FF3F3F',
					"background": "#FAEBE7"
					});
					return false;
				}
				
				var isbn = $('#Print').val();
				if(isbn.length < 10 || (isbn.length > 10 && isbn.length < 13) ){
					$.notify("ISBN length is not correct..!!", {autoHide: true,className: 'error',globalPosition: 'top right', autoHideDelay: 3000});
					return false;
				}
				
				if($('#Author').val() == ''){
					$("#Author").css({
					'border': '1px dashed #FF3F3F',
					"background": "#FAEBE7"
					});
					return false;
				}

				window.onbeforeunload = function () {
					return 'You have unsaved changes.';
				};

				var file = data.files[0];
				var filename = file.name;
				var fext = filename.substring(filename.lastIndexOf('.')+1);
				
				if(fext 	!=	"docx" && fext 	!=	"doc"){
					$.notify("Invalid file format", {autoHide: true,className: 'error',globalPosition: 'top right', autoHideDelay: 3000});
					return false;
				}
		
				form.find('input[name="Content-Type"]').val(file.type);
				form.find('input[name="Content-Length"]').val(file.size);
				var filedesname	=	"uploadedFile/"+acronym+"/HANDOVER_FORM/"+filename;
				form.find('input[name="key"]').val(filedesname);
				data.submit();
				
				var bar = $('<div class="handover-progress" data-mod="'+file.size+'"><div class="bar"></div></div>');
				$('.handover-bar-area').empty();
				$('.handover-bar-area').append(bar);
				bar.slideDown('fast');
			},
			progress: function (e, data) {
				
				var percent = Math.round((data.loaded / data.total) * 100);
				$('.handover-progress[data-mod="'+data.files[0].size+'"] .bar').css('width', percent + '%').html(percent+'%');
			},
			fail: function (e, data) {
				
				// window.onbeforeunload = null;
				$('.handover-progress[data-mod="'+data.files[0].size+'"] .bar').css('width', '100%').addClass('red').html('Failed');
			},
			done: function (event, data) {
				// window.onbeforeunload = null;
				var original = data.files[0];
				var s3Result = data.result.documentElement.childNodes;
				filesUploaded.push({
					"original_name": original.name,
					"s3_name": s3Result[2].textContent,
					"size": original.size,
					"url": s3Result[0].textContent.replace("%2F", "/")
				});
				$('#uploaded').html(JSON.stringify(filesUploaded, null, 2));
			}
		});
	});
	
	//title passport file upload
	$(document).ready(function(){
		var form 	= 	$('.passport-upload');
		var filesUploaded = [];					
		var folders = 	[];
		form.fileupload({
			
			url: "//oupbucket.s3-accelerate.amazonaws.com/",
			type: "post",
			datatype: 'xml',
			add: function (event, data) {
				var acronym = 	$('#acronym').val();
				if(acronym == ""){
					$("#acronym").css({
					'border': '1px dashed #FF3F3F',
					"background": "#FAEBE7"
					});
					return false;
				}
				
				var isbn = $('#Print').val();
				if(isbn.length < 10 || (isbn.length > 10 && isbn.length < 13) ){
					$.notify("ISBN length is not correct..!!", {autoHide: true,className: 'error',globalPosition: 'top right', autoHideDelay: 3000});
					return false;
				}
				
				if($('#Author').val() == ''){
					$("#Author").css({
					'border': '1px dashed #FF3F3F',
					"background": "#FAEBE7"
					});
					return false;
				}

				window.onbeforeunload = function () {
					return 'You have unsaved changes.';
				};

				var file = data.files[0];
				var filename = file.name;
				var fext = filename.substring(filename.lastIndexOf('.')+1);
				
				if(fext 	!=	"docx" && fext 	!=	"doc"){
					$.notify("Invalid file format", {autoHide: true,className: 'error',globalPosition: 'top right', autoHideDelay: 3000});
					return false;
				}
		
				form.find('input[name="Content-Type"]').val(file.type);
				form.find('input[name="Content-Length"]').val(file.size);
				var filedesname	=	"uploadedFile/Manuscript/"+acronym+"/"+filename;
				form.find('input[name="key"]').val(filedesname);
				data.submit();
				
				var bar = $('<div class="passport-progress" data-mod="'+file.size+'"><div class="bar"></div></div>');
				$('.passport-bar-area').empty();
				$('.passport-bar-area').append(bar);
				bar.slideDown('fast');
			},
			progress: function (e, data) {
				
				var percent = Math.round((data.loaded / data.total) * 100);
				$('.passport-progress[data-mod="'+data.files[0].size+'"] .bar').css('width', percent + '%').html(percent+'%');
			},
			fail: function (e, data) {
				
				// window.onbeforeunload = null;
				$('.passport-progress[data-mod="'+data.files[0].size+'"] .bar').css('width', '100%').addClass('red').html('Failed');
			},
			done: function (event, data) {
				// window.onbeforeunload = null;
				var original = data.files[0];
				var s3Result = data.result.documentElement.childNodes;
				filesUploaded.push({
					"original_name": original.name,
					"s3_name": s3Result[2].textContent,
					"size": original.size,
					"url": s3Result[0].textContent.replace("%2F", "/")
				});
				$('#uploaded').html(JSON.stringify(filesUploaded, null, 2));
			}
		});
	});
	//manuscript file upload
	$(document).ready(function(){
		var form 	= 	$('.manuscript-upload');
		var filesUploaded = [];					
		var folders = 	[];
		form.fileupload({
			
			url:"//oupbucket.s3-accelerate.amazonaws.com/",
			type: "post",
			datatype: 'xml',
			add: function (event, data) {
				var acronym = 	$('#acronym').val();
				if(acronym == ""){
					$("#acronym").css({
					'border': '1px dashed #FF3F3F',
					"background": "#FAEBE7"
					});
					return false;
				}
				
				var isbn = $('#Print').val();
				if(isbn.length < 10 || (isbn.length > 10 && isbn.length < 13) ){
					$.notify("ISBN length is not correct..!!", {autoHide: true,className: 'error',globalPosition: 'top right', autoHideDelay: 3000});
					return false;
				}
				
				if($('#Author').val() == ''){
					$("#Author").css({
					'border': '1px dashed #FF3F3F',
					"background": "#FAEBE7"
					});
					return false;
				}

				window.onbeforeunload = function () {
					return 'You have unsaved changes.';
				};

				var file = data.files[0];
				var filename = file.name;
				var fext = filename.substring(filename.lastIndexOf('.')+1);
		
				form.find('input[name="Content-Type"]').val(file.type);
				form.find('input[name="Content-Length"]').val(file.size);
				var filedesname	=	"uploadedFile/Manuscript/"+acronym+"/"+filename;
				form.find('input[name="key"]').val(filedesname);
				data.submit();
				
				var bar = $('<div class="manuscript-progress" data-mod="'+file.size+'"><div class="bar"></div></div>');
				$('.manuscript-bar-area').empty();
				$('.manuscript-bar-area').append(bar);
				bar.slideDown('fast');
			},
			progress: function (e, data) {
				
				var percent = Math.round((data.loaded / data.total) * 100);
				$('.manuscript-progress[data-mod="'+data.files[0].size+'"] .bar').css('width', percent + '%').html(percent+'%');
			},
			fail: function (e, data) {
				
				// window.onbeforeunload = null;
				$('.manuscript-progress[data-mod="'+data.files[0].size+'"] .bar').css('width', '100%').addClass('red').html('Failed');
			},
			done: function (event, data) {
				// window.onbeforeunload = null;
				var original = data.files[0];
				var s3Result = data.result.documentElement.childNodes;
				filesUploaded.push({
					"original_name": original.name,
					"s3_name": s3Result[2].textContent,
					"size": original.size,
					"url": s3Result[0].textContent.replace("%2F", "/")
				});
				$('#uploaded').html(JSON.stringify(filesUploaded, null, 2));
			}
		});
	});
</script>

<!--</form>-->
</div>
<?php include(BASE_PATH.'footer.php'); ?>  
</div>