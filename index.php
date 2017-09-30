<html>
<head>
<style type="text/css">
	#result_1 {
		line-height:15px;
		padding:20px;
		border:2px blue solid;
		float:left;
	}
	#result_2 {
		line-height:15px;
		padding:20px;
		border:2px green solid;
		float:left;
	}
</style>
<script type="text/javascript" src="js/jquery-3.1.1.min.js?v=<?php echo time(); ?>"></script>
<script>
function ajax_multi_order(){
	var URLs="./lib/test.redis_curl.php";
	$.ajax({
		url: URLs,
		type:"GET",
		dataType:"text",
		//*取得當日已結算的期數 
		success: function(data){
			$("#result_1").html(data);
		}
	});
}
function ajax_select_order(){
	var URLs="./lib/test.redis_PDO.php?c=2";
	$.ajax({
		url: URLs,
		type:"GET",
		dataType:"text",
		//*取得當日已結算的期數 
		success: function(data){
			$("#result_2").html(data);
		}
	});
}
window.setInterval("ajax_multi_order();",10*1000);
//window.setInterval("ajax_select_order();",5*1000);
</script>
</head>
<body>
	<div id="result_1"></div>
	<!--<div id="result_2"></div>-->
</body>
</html>