<?php  
####### By Chel : Dated 15-02-2018 #######
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
include_once BASE_PATH.'aws_config.php';
require_once __DIR__."/vendor/autoload.php";

function s3($command=null,$args=null)
{
	static $s3=null;
	if ($s3===null)
	$s3 = new Aws\S3\S3Client([
	    'version' => 'latest',
	    'region'  => 'ap-south-1',
	    'signature_version' => 'v4',
	        'credentials' => [
	        'key'    => AWS_ACCESS_KEY,//AKIAJTWIFZKPJAX5AMSQ
	        'secret' => AWS_SECRET_KEY,//E+75RAeG+XZS34gHJPA/BNr+vE65oZwXJTV8Kf2w
	    ]
	]);
	if ($command===null)
		return $s3;
	$args=func_get_args();
	array_shift($args);
	try {
		$res=call_user_func_array([$s3,$command],$args);
		return $res;
	}
	catch (AwsException $e)
	{
		echo $e->getMessage(),PHP_EOL;
	}	
	return null;
}

function bucket() {
	return AWS_BUCKET_NAME;//oupbucket
}

function abortPendingUploads($bucket)
{
    $count=0;
    $res=s3("listMultipartUploads",["Bucket"=>bucket()]);
    if (is_array($res["Uploads"]))
    foreach ($res["Uploads"] as $item)
    {
        $r=s3("abortMultipartUpload",["Bucket"=>$bucket,"Key"=>$item["Key"],"UploadId"=>$item["UploadId"],]);
        $count++;
    }
    return $count;
}

function json_output($data)
{
    header('Content-Type: application/json');
    die(json_encode($data));
}

if (isset($_POST['command']))
{
	$command	=	$_POST['command'];
	if ($command=="create")
	{		
		$type		=	$_POST['type'];
		$acronym	=	$_POST['acronym'];
		$filename 	=	$_POST['fileInfo']['name'];
		$fileext 	= 	pathinfo($filename, PATHINFO_EXTENSION);
		
		if(($type 	==	"handoverfile" || $type 	==	"passportfile") && ($fileext 	!=	"docx" && $fileext 	!=	"doc")){
			$message 		=	"Invalid File Format";	
			die($message);
			exit($message);
		}
		
		// $destFolder =	($type 	==	"handoverfile"?"uploadedFile/".$acronym."/HANDOVER_FORM/".$filename:"uploadedFile/Manuscript/".$acronym."/".$filename);
		$destFolder =	($type 	==	"handoverfile"?"uploadedFile/".$acronym."/HANDOVER_FORM/".$filename:"uploadedFile/".$acronym."/00-INPUT/".$filename);
		$res=s3("createMultipartUpload",[
			'Bucket' => bucket(),
            'Key' => $destFolder,
            'ContentType' => $_REQUEST['fileInfo']['type'],
            'Metadata' => $_REQUEST['fileInfo']
		]);
	 	json_output(array(
               'uploadId' => $res->get('UploadId'),
                'key' => $res->get('Key'),
        ));
	}

	if ($command=="part")
	{
		$command=s3("getCommand","UploadPart",[
			'Bucket' => bucket(),
            'Key' => $_REQUEST['sendBackData']['key'],
            'UploadId' => $_REQUEST['sendBackData']['uploadId'],
            'PartNumber' => $_REQUEST['partNumber'],
            'ContentLength' => $_REQUEST['contentLength']
		]);
        // Give it at least 24 hours for large uploads
		$request=s3("createPresignedRequest",$command,"+48 hours");
        json_output([
            'url' => (string)$request->getUri(),
        ]);		
	}

	if ($command=="complete")
	{
	 	$partsModel = s3("listParts",[
            'Bucket' => bucket(),
            'Key' => $_REQUEST['sendBackData']['key'],
            'UploadId' => $_REQUEST['sendBackData']['uploadId'],
        ]);
		
		if(isset($partsModel["Parts"]) && !is_array($partsModel["Parts"])){
			echo "die";exit;
		}
		
        $model = s3("completeMultipartUpload",[
            'Bucket' => bucket(),
            'Key' => $_REQUEST['sendBackData']['key'],
            'UploadId' => $_REQUEST['sendBackData']['uploadId'],
            'MultipartUpload' => [
            	"Parts"=>$partsModel["Parts"],
            ],
        ]);
		
        json_output([
            'success' => true
        ]);
	}
	if ($command=="abort")
	{
		 $model = s3("abortMultipartUpload",[
            'Bucket' => bucket(),
            'Key' => $_REQUEST['sendBackData']['key'],
            'UploadId' => $_REQUEST['sendBackData']['uploadId']
        ]);
        json_output([
            'success' => true
        ]);
	}

	exit(0);
}

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
.fileinput-button {
  position: relative;
  overflow: hidden;
  display: inline-block;
}
.fileinput-button input {
  position: absolute;
  top: 0;
  right: 0;
  margin: 0;
  opacity: 0;
  -ms-filter: 'alpha(opacity=0)';
  font-size: 200px !important;
  direction: ltr;
  cursor: pointer;
}

.handprogress {
	position:relative;
	height: 20px;
	margin-bottom: 20px;
	overflow: hidden;
	background-color: #f5f5f5;
	border-radius: 4px;
	-webkit-box-shadow: inset 0 1px 2px rgba(0,0,0,.1);
	box-shadow: inset 0 1px 2px rgba(0,0,0,.1);
}

.passprogress {
	position:relative;
	height: 20px;
	margin-bottom: 20px;
	overflow: hidden;
	background-color: #f5f5f5;
	border-radius: 4px;
	-webkit-box-shadow: inset 0 1px 2px rgba(0,0,0,.1);
	box-shadow: inset 0 1px 2px rgba(0,0,0,.1);
}

.manuprogress {
	position:relative;
	height: 20px;
	margin-bottom: 20px;
	overflow: hidden;
	background-color: #f5f5f5;
	border-radius: 4px;
	-webkit-box-shadow: inset 0 1px 2px rgba(0,0,0,.1);
	box-shadow: inset 0 1px 2px rgba(0,0,0,.1);
}

.handprogress-number {
	position:absolute;
	left:50%;
	z-index:5;
	color:white;
    font-family: sans-serif;
}

.passprogress-number {
	position:absolute;
	left:50%;
	z-index:5;
	color:white;
	font-family: sans-serif;
}

.manuprogress-number {
	position:absolute;
	left:50%;
	z-index:5;
	color:white;
	font-family: sans-serif;
}

.handprogress-bar {
	float: left;
	width: 0;
	height: 100%;
	font-size: 15px;
	line-height: 20px;
	color: #fff;
	text-align: center;
	background-color: #009688;
	-webkit-box-shadow: inset 0 -1px 0 rgba(0,0,0,.15);
	box-shadow: inset 0 -1px 0 rgba(0,0,0,.15);
	-webkit-transition: width .6s ease;
	-o-transition: width .6s ease;
	transition: width .6s ease;
}

.passprogress-bar {
	float: left;
	width: 0;
	height: 100%;
	font-size: 15px;
	line-height: 20px;
	color: #fff;
	text-align: center;
	background-color: #009688;
	-webkit-box-shadow: inset 0 -1px 0 rgba(0,0,0,.15);
	box-shadow: inset 0 -1px 0 rgba(0,0,0,.15);
	-webkit-transition: width .6s ease;
	-o-transition: width .6s ease;
	transition: width .6s ease;
}

.manuprogress-bar {
	float: left;
	width: 0;
	height: 100%;
	font-size: 15px;
	line-height: 20px;
	color: #fff;
	text-align: center;
	background-color: #009688;
	-webkit-box-shadow: inset 0 -1px 0 rgba(0,0,0,.15);
	box-shadow: inset 0 -1px 0 rgba(0,0,0,.15);
	-webkit-transition: width .6s ease;
	-o-transition: width .6s ease;
	transition: width .6s ease;
}

.button {
	color: #fff;
	background-color: #5cb85c;
	border-color: #4cae4c;

	display: inline-block;
	padding: 6px 12px;
	margin-bottom: 0;
	font-size: 14px;
	font-weight: 400;
	line-height: 1.42857143;
	text-align: center;
	white-space: nowrap;
	vertical-align: middle;
	-ms-touch-action: manipulation;
	touch-action: manipulation;
	cursor: pointer;
	-webkit-user-select: none;
	-moz-user-select: none;
	-ms-user-select: none;
	user-select: none;
	background-image: none;
	border: 1px solid transparent;
	border-radius: 4px;
}
/* Fixes for IE < 8 */
@media screen\9 {
  .fileinput-button input {
    filter: alpha(opacity=0);
    font-size: 100%;
    height: 100%;
  }
}

#handprogress {
	border:1px solid gray;
	margin:5px;
	display:none;
    background-color: hsl(0, 0%, 47%);
}

#passprogress {
	border:1px solid gray;
	margin:5px;
	display:none;
	background-color: hsl(0, 0%, 47%);
}

#manuprogress {
	border:1px solid gray;
	margin:5px;
	display:none;
	background-color: hsl(0, 0%, 47%);
}
</style>

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
		
		$('.fileuploadedstatus').each(function(){
			var valstatus = $(this).val();
			console.log(valstatus);
			if(valstatus == "" || valstatus == 0)
			{
				$.notify("File uploaded is failed kindly try again..!!", {autoHide: true,className: 'error',globalPosition: 'top right', autoHideDelay: 3000});
				return false;
			}
		});
		
		
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
			url:'title_creationAjax_aws.php',
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
});

function titleEmailNotification(job_id)
{
	$.ajax({
		url:"title_creationAjax_aws.php",
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
		url:"title_creationAjax_aws_test.php",
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
<form method="post" action="" name="titlecreation" id="titlecreation"enctype="multipart/form-data">


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
		<input type="hidden" id="project_type_select" name="project_type_select" value=""/>
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
			<input type="hidden" id="handfilename" name="handfilename" value=""/>
			<input type="file" name="file" id="handoverfile" accept=".docx,.doc,.docs">
		</div>
		</div>
		<input type="button" data-nameofthefile="handoverfile" class="FileUpload" name="handoverFileUpload" id="handoverFileUpload" value="Upload" style="margin-top: -30px;margin-left: 52%;"/><br/><br/>
		<div id="handprogress" class="handprogress">
			<div class="handprogress-bar progress-bar-success"></div>
			<div class="handprogress-number"></div>
		</div>
		<span id="result1"></span>
		</td>
	</tr>
	<tr>
		<td>Title Passport<!--<span class="spancolor">*</span>--></td>
		<td colspan="3">
		<div class="uploadfile">
		<div class="filerow passtitle" id="filerow">
			<input type="hidden" id="passfilename" name="passfilename" value=""/>
			<input type="file" name="file" id="passportfile" accept=".docx,.doc,.docs">
		</div>
		</div>
		<input type="button" data-nameofthefile="passportfile" class="FileUpload" name="passFileUpload" id="passFileUpload" value="Upload" style="margin-top: -30px;margin-left: 52%;"/><br/><br/>
		<div id="passprogress" class="passprogress">
			<div class="passprogress-bar progress-bar-success"></div>
			<div class="passprogress-number"></div>
		</div>
		<span id="result2"></span>
		</td>
	</tr>
	<tr>
		<td>Manuscript<span class="spancolor">*</span></td>
		<td colspan="3">
		<div class="uploadfile">
		<div class="filerow titlehand" id="filerow">
			<input type="hidden" id="filename" name="filename" value=""/>
			<input type="hidden" id="upDir" name="upDir" value=""/>
			<input type="hidden" id="handDir" class="fileuploadedstatus" name="handDir" value=""/>
			<input type="hidden" id="manDir" name="manDir" class="fileuploadedstatus" value=""/>
			<input type="file" name="file" id="titlefile">
		</div>
		</div>
		
		<input type="button" data-nameofthefile="manuscriptFile" class="FileUpload" name="manFileUpload" id="manFileUpload" value="Upload" style="margin-top: -30px;margin-left: 52%;"/><br/><br/>
		<div id="manuprogress" class="manuprogress">
			<div class="manuprogress-bar progress-bar-success"></div>
			<div class="manuprogress-number"></div>
		</div>
		<span id="result3"></span>
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
</form>
<script type="text/javascript" src="script/jquery-ui.min.js"></script>
<script type="text/javascript" src="script/s3upload.js"></script>
<script>

	//handover file upload
	$(document).ready(function(){
		
		$("#project_type").change(function(){
			$("#project_type_select").val($(this).val());
		});
		
		function Elementchange(type,btn){
			if(type 	==	"add"){
				$("#"+btn).attr('disabled','disabled');
				$("#"+btn).css({'background':'#795548','cursor':'none'});
				$("#"+btn).val("In progress");
			}else if(type 	==	"completed"){
				$("#"+btn).attr('disabled','disabled');
				$("#"+btn).css({'background':'#795548','cursor':'none'});
				$("#"+btn).val("Completed");
				if(btn 	==	"manFileUpload"){
					$("#titlefile").attr('disabled','disabled');
				}else if(btn 	==	"passFileUpload"){
					$("#passportfile").attr('disabled','disabled');
				}else{
					$("#handoverfile").attr('disabled','disabled');
				}
			}
			else{
				$("#"+btn).removeAttr('disabled');
				$("#"+btn).css({'background':'#4E9ACF','cursor':'pointer'});
				$("#"+btn).val("Upload");
				if(btn 	==	"manFileUpload"){
					$("#titlefile").removeAttr('disabled');
				}else if(btn 	==	"passFileUpload"){
					$("#passportfile").removeAttr('disabled');
				}else{
					$("#handoverfile").removeAttr('disabled');
				}
			}
		}
	
		$(".FileUpload").on("click",function() {
			var getcurrentuploadFile 	=	$(this).attr('data-nameofthefile');
			if(getcurrentuploadFile 	==	"" || getcurrentuploadFile 	==	undefined){
				$.notify("Invalid data format", {autoHide: true,className: 'error',globalPosition: 'top right', autoHideDelay: 3000});
				return false;
			}
			
			var acronym 	= 	$('#acronym').val();
			$("#upDir").val(acronym);
			var file_data 		=	"";
			if(getcurrentuploadFile 	==	"manuscriptFile"){
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
			}else if(getcurrentuploadFile 	==	"handoverfile"){
				$('#handfilename').val(fname+'^'+fext+'^'+fsize);
				if(fext 	!=	"docx" && fext 	!=	"doc"){
					$.notify("Invalid file format", {autoHide: true,className: 'error',globalPosition: 'top right', autoHideDelay: 3000});
					return false;
				}		
			}else{
				if(fext 	!==	"docx" && fext 	!==	"doc"){
					$.notify("Invalid file format", {autoHide: true,className: 'error',globalPosition: 'top right', autoHideDelay: 3000});
					return false;
				}
				$('#passfilename').val(fname+'^'+fext+'^'+fsize);	
			}
			
			if(acronym == ""){
				$("#acronym").css({
				'border': '1px dashed #FF3F3F',
				"background": "#FAEBE7"
				});
				return false;
			}
			
			if($('#Author').val() == ''){
				$("#Author").css({
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
			
			if(getcurrentuploadFile 	==	"manuscriptFile"){
				manuscriptFileupload(file_data,"manuscriptFile",acronym,1);
			}else if(getcurrentuploadFile 	==	"handoverfile"){
				handoverfileupload(file_data,"handoverfile",acronym,1);
			}else{
				passportFileupload(file_data,"passportfile",acronym,1);	
			}
		});
		
		var s3manuscriptupload=null;
		function manuscriptFileupload(file,type,acronym,iteration) {
			if (!(window.File && window.FileReader && window.FileList && window.Blob && window.Blob.prototype.slice)) {
				alert("You are using an unsupported browser. Please update your browser.");
				return;
			}
			if(iteration 	==	6){
				$.notify("Upload Failed tried more than five times, Kindly retry.", {autoHide: true,className: 'error',globalPosition: 'top right', autoHideDelay: 3000});		
				return false;
			}
			$("#result3").css("color","#666");
			var progressBar 	=	"";
			var progressClass 	=	"";
			var progressId 		=	"";
			var progressNumber 	=	"";
			var urlLinkName		=	"";
			progressBar 	=	"manuprogress";
			progressId 		=	"manuprogress";
			progressBar 	=	"manuprogress-bar";
			progressNumber 	=	"manuprogress-number";
			resultId 		=	"result3"
			urlLinkName 	=	"manFileUpload";
			// change element style inprogress and undo old
			Elementchange('add',urlLinkName);
			
			$("#result3").text("");
			$('#'+progressId).css('display',"block");
			$('#'+progressId+' .'+progressBar).css('width',"0px");
			$('#'+progressId+' .'+progressNumber).text("");

			s3manuscriptupload = new S3MultiUpload(file);
			s3manuscriptupload.onServerError = function(command, jqXHR, textStatus, errorThrown) {
				Elementchange('remove',urlLinkName);
				$("#result3").text("Upload failed with server error.");
				$("#result3").css("color","red");
			};
			s3manuscriptupload.onS3UploadError = function(xhr) {
				Elementchange('remove',urlLinkName);
				$("#result3").text("Upload to S3 failed.");
				$("#result3").css("color","red");
			};
			s3manuscriptupload.onProgressChanged = function(uploadedSize, totalSize, speed) {
				var progress = parseInt(uploadedSize / totalSize * 100, 10);
				$('#'+progressId+' .'+progressBar).css(
					'width',
					progress + '%'
				);
				$("."+progressNumber).html(manuscriptgetReadableFileSizeString(uploadedSize)+" / "+manuscriptgetReadableFileSizeString(totalSize)
					+ " <span style='font-size:12px;font-family: sans-serif;color:white;'>("
					+uploadedSize+" / "+totalSize
					+" at "
					+manuscriptgetReadableFileSizeString(speed)+"ps"
					+")</span>").css({'margin-left' : -$('.'+progressNumber).width()/2});

			};
			s3manuscriptupload.onPrepareCompleted = function() {
				$("#result3").text("Uploading...");
			}
			s3manuscriptupload.onUploadCompleted = function(data) {
				if(Object.prototype.toString.call(data) === '[object Object]'){
					Elementchange('completed',urlLinkName);	
					$("#manDir").val(1);					
					$("#titlecreation").find(':input').prop("readonly", true);
					$('#project_type').prop('disabled',true);
					$("#result3").text("Upload successful.");
					$.notify("Upload successful.", {autoHide: true,className: 'success',globalPosition: 'top right', autoHideDelay: 3000});	
				}else{
					Elementchange('remove',urlLinkName);
					$("#result3").text("Upload Failed.");
					$("#result3").css("color","red");
					$('#'+progressId+' .'+progressBar).css('width',"0%");
					$.notify("Upload Failed.", {autoHide: true,className: 'error',globalPosition: 'top right', autoHideDelay: 3000});	
					iteration++;					
					manuscriptretryUpload(type,acronym,iteration);
				}
			};
			$("#result3").text("Preparing upload...");
			s3manuscriptupload.start(type,acronym);
		}
		
		function manuscriptretryUpload(type,acronym,iteration){
			var file_data 		= 	$('#titlefile').prop('files')[0];
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
			manuscriptFileupload(file_data,"manuscriptFile",acronym,iteration);
		}
		
		function handscriptretryUpload(type,acronym,iteration){
			var file_data 		= 	$('#handoverfile').prop('files')[0];
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
			handoverfileupload(file_data,"handoverfile",acronym,iteration);
		}
		
		function passscriptretryUpload(type,acronym,iteration){
			var file_data 		= 	$('#passportfile').prop('files')[0];
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
			passportFileupload(file_data,"passportfile",acronym,iteration);
		}
		
		
		var s3handoverfileupload=null;
		function handoverfileupload(file,type,acronym,iteration) {
			if (!(window.File && window.FileReader && window.FileList && window.Blob && window.Blob.prototype.slice)) {
				alert("You are using an unsupported browser. Please update your browser.");
				return;
			}
			if(iteration 	==	6){
				$.notify("Upload Failed tried more than five times, Kindly retry.", {autoHide: true,className: 'error',globalPosition: 'top right', autoHideDelay: 3000});		
				return false;
			}
			$("#result1").css("color","#666");
			var progressBar 	=	"";
			var progressClass 	=	"";
			var progressId 		=	"";
			var progressNumber 	=	"";
			var urlLinkName		=	"";
			
			progressBar 	=	"handprogress";
			progressId 		=	"handprogress";
			progressBar 	=	"handprogress-bar";
			progressNumber 	=	"handprogress-number";
			resultId 		=	"result1"
			urlLinkName 	=	"handoverFileUpload";
			
			// change element style inprogress and undo old
			Elementchange('add',urlLinkName);
			
			$("#result1").text("");
			$('#'+progressId).css('display',"block");
			$('#'+progressId+' .'+progressBar).css('width',"0px");
			$('#'+progressId+' .'+progressNumber).text("");

			s3handoverfileupload = new S3MultiUpload(file);
			s3handoverfileupload.onServerError = function(command, jqXHR, textStatus, errorThrown) {
				Elementchange('remove',urlLinkName);
				$("#result1").text("Upload failed with server error.");
				$("#result1").css("color","red");
			};
			s3handoverfileupload.onS3UploadError = function(xhr) {
				Elementchange('remove',urlLinkName);
				$("#result1").text("Upload to S3 failed.");
				$("#result1").css("color","red");
			};
			s3handoverfileupload.onProgressChanged = function(uploadedSize, totalSize, speed) {
				var progress = parseInt(uploadedSize / totalSize * 100, 10);
				$('#'+progressId+' .'+progressBar).css(
					'width',
					progress + '%'
				);
				$("."+progressNumber).html(handovergetReadableFileSizeString(uploadedSize)+" / "+handovergetReadableFileSizeString(totalSize)
					+ " <span style='font-size:12px;font-family: sans-serif;color:white;'>("
					+uploadedSize+" / "+totalSize
					+" at "
					+handovergetReadableFileSizeString(speed)+"ps"
					+")</span>").css({'margin-left' : -$('.'+progressNumber).width()/2});

			};
			s3handoverfileupload.onPrepareCompleted = function() {
				$("#result1").text("Uploading...");
			}
			s3handoverfileupload.onUploadCompleted = function(data) {
				if(Object.prototype.toString.call(data) === '[object Object]'){
					Elementchange('completed',urlLinkName);	
					$("#result1").text("Upload successful.");
					$("#handDir").val(1);
					$("#titlecreation").find(':input').prop("readonly", true);
					$('#project_type').prop('disabled',true);
					$.notify("Upload successful.", {autoHide: true,className: 'success',globalPosition: 'top right', autoHideDelay: 3000});	
				}else{
					Elementchange('remove',urlLinkName);
					$("#result1").text("Upload Failed.");
					$("#result1").css("color","red");
					$('#'+progressId+' .'+progressBar).css('width',"0%");
					$.notify("Upload Failed.", {autoHide: true,className: 'error',globalPosition: 'top right', autoHideDelay: 3000});	
					iteration++;					
					handscriptretryUpload(type,acronym,iteration);					
				}
			};
			$("#result1").text("Preparing upload...");
			s3handoverfileupload.start(type,acronym);
		}
		
		var s3passportupload=null;
		function passportFileupload(file,type,acronym,iteration) {
			if (!(window.File && window.FileReader && window.FileList && window.Blob && window.Blob.prototype.slice)) {
				alert("You are using an unsupported browser. Please update your browser.");
				return;
			}
			if(iteration 	==	6){
				$.notify("Upload Failed tried more than five times, Kindly retry.", {autoHide: true,className: 'error',globalPosition: 'top right', autoHideDelay: 3000});		
				return false;
			}
			$("#result2").css("color","#666");
			var progressBar 	=	"";
			var progressClass 	=	"";
			var progressId 		=	"";
			var progressNumber 	=	"";
			var urlLinkName		=	"";
			progressBar 	=	"passprogress";
			progressId 		=	"passprogress";
			progressBar 	=	"passprogress-bar";
			progressNumber 	=	"passprogress-number";
			resultId 		=	"result2"
			urlLinkName 	=	"passFileUpload";
			// change element style inprogress and undo old
			Elementchange('add',urlLinkName);
			
			$("#result2").text("");
			$('#'+progressId).css('display',"block");
			$('#'+progressId+' .'+progressBar).css('width',"0px");
			$('#'+progressId+' .'+progressNumber).text("");

			s3passportupload = new S3MultiUpload(file);
			s3passportupload.onServerError = function(command, jqXHR, textStatus, errorThrown) {
				Elementchange('remove',urlLinkName);
				$("#result2").text("Upload failed with server error.");
				$("#result2").css("color","red");
			};
			s3passportupload.onS3UploadError = function(xhr) {
				Elementchange('remove',urlLinkName);
				$("#result2").text("Upload to S3 failed.");
				$("#result2").css("color","red");
			};
			s3passportupload.onProgressChanged = function(uploadedSize, totalSize, speed) {
				var progress = parseInt(uploadedSize / totalSize * 100, 10);
				$('#'+progressId+' .'+progressBar).css(
					'width',
					progress + '%'
				);
				$("."+progressNumber).html(passgetReadableFileSizeString(uploadedSize)+" / "+passgetReadableFileSizeString(totalSize)
					+ " <span style='font-size:12px;font-family: sans-serif;color:white;'>("
					+uploadedSize+" / "+totalSize
					+" at "
					+passgetReadableFileSizeString(speed)+"ps"
					+")</span>").css({'margin-left' : -$('.'+progressNumber).width()/2});

			};
			s3passportupload.onPrepareCompleted = function() {
				$("#result2").text("Uploading...");
			}
			s3passportupload.onUploadCompleted = function(data) {
				if(Object.prototype.toString.call(data) === '[object Object]'){
					Elementchange('completed',urlLinkName);	
					$("#result2").text("Upload successful.");
					$.notify("Upload successful.", {autoHide: true,className: 'success',globalPosition: 'top right', autoHideDelay: 3000});	
				}else{
					Elementchange('remove',urlLinkName);
					$("#result2").text("Upload Failed.");
					$("#result2").css("color","red");
					$('#'+progressId+' .'+progressBar).css('width',"0%");
					$.notify("Upload Failed.", {autoHide: true,className: 'error',globalPosition: 'top right', autoHideDelay: 3000});		
					iteration++;					
					passscriptretryUpload(type,acronym,iteration);
				}
			};
			$("#result2").text("Preparing upload...");
			s3passportupload.start(type,acronym);
		}
		
		function manuscriptgetReadableFileSizeString(fileSizeInBytes) {
			var i = -1;
			var byteUnits = [' KB', ' MB', ' GB', ' TB', 'PB', 'EB', 'ZB', 'YB'];
			do {
				fileSizeInBytes = fileSizeInBytes / 1024;
				i++;
			} while (fileSizeInBytes > 1024);

			return Math.max(fileSizeInBytes, 0.1).toFixed(1) + byteUnits[i];
		}
		
		function handovergetReadableFileSizeString(fileSizeInBytes) {
			var i = -1;
			var byteUnits = [' KB', ' MB', ' GB', ' TB', 'PB', 'EB', 'ZB', 'YB'];
			do {
				fileSizeInBytes = fileSizeInBytes / 1024;
				i++;
			} while (fileSizeInBytes > 1024);

			return Math.max(fileSizeInBytes, 0.1).toFixed(1) + byteUnits[i];
		}
		
		function passgetReadableFileSizeString(fileSizeInBytes) {
			var i = -1;
			var byteUnits = [' KB', ' MB', ' GB', ' TB', 'PB', 'EB', 'ZB', 'YB'];
			do {
				fileSizeInBytes = fileSizeInBytes / 1024;
				i++;
			} while (fileSizeInBytes > 1024);

			return Math.max(fileSizeInBytes, 0.1).toFixed(1) + byteUnits[i];
		}
		
	});	
</script>

<!--</form>-->
</div>
<?php include(BASE_PATH.'footer.php'); ?>  
</div>