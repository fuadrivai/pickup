$(document).ready(function() {
    update();
    selesai();
    setTimeout(function(){
        $('.form-user').css('display','block');
        document.querySelector('input').focus()
},2000);

});
var input = document.getElementById("myInput");
input.addEventListener("keypress", function(event) {
  if (event.key === "Enter") {
    event.preventDefault();
    document.getElementById("myBtn").click();
  }
});
$(".tombol-simpan").click(function(){
	var data = $('.form-user').serialize();
	document.getElementById("myInput").value = "";
	$.ajax({
		type: 'POST',
		url: "https://whatsapp.mhis.link/filewebhook/pickup/simpan.php",
		data: data,
		success: function() {
// 			alert('input data berhasil');
$.ajaxSetup({ cache: false});

		}
	});
}); 
function selesai() {
	setTimeout(function() {
	   // play();
		update();
		selesai();
	}, 1000);
}
 
function update() {
    $.ajaxSetup({ cache: false});
	$.getJSON("https://whatsapp.mhis.link/filewebhook/pickup/data.php", function(data) {
	    $.ajaxSetup({ cache: false});
		$("tbody").empty();
		if (data.result.length === 0) {
            $("#col1").hide();
            $("#col2").show();
        } else {
            $("#col1").show();
            $("#col2").hide();
            
		}
		var no = 1;
		$.each(data.result, function() {
			$.ajaxSetup({ cache: false});
			$("tbody").append("<tr><td style='width:80%'>"+this['student_name']+"</td><td style='width:20%'> "+this['grade']+"</td></tr>");
		});
	});
}
// function play() {
//     $.ajaxSetup({ cache: false});
//     $.getJSON("https://mutiaraharapan.sch.id/pickup/audio.php", function(data) {
// 	    $.ajaxSetup({ cache: false});
// 		$.each(data.result, function() {
// 		    var audioFiles = ["https://mutiaraharapan.sch.id/pickup/audio/"+this['rfidid']+".mp3", "https://mutiaraharapan.sch.id/pickup/audio/"+this['grade']+".mp3"];
// 		    var audio = document.createElement("audio");
// 		    var audioIdx = 0;
// 		    var loopCount = 0;
// 		    audio.addEventListener('ended', function () {
// 		        audioIdx++;
// 		        if (this.currentTime === 0);
//                 ++loopCount;
// 		        if (audioIdx >= audioFiles.length) audioIdx = 0;
// 		        this.src = audioFiles[audioIdx];
// 		        this.play();
// 		        if (loopCount == 4)
//                 this.pause();
// 		    });
		    
// 		    audio.src = audioFiles[audioIdx];
// 		    audio.play();
		    
		    
// 		$.ajax({
// 		type: 'POST',
// 		url: "updateaudio.php",
// 		data: JSON.stringify({ "nama": this['rfidid']}),
// 		contentType: "application/json",
// 		success: function() {
// 		}
// 	});
// 		});
// 	});
// }
