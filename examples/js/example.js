
function loadXMLDoc() {
    var xmlhttp;

    xmlhttp = new XMLHttpRequest();

    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
            document.getElementById("myDiv").innerHTML = xmlhttp.responseText;
        }
    };

    xmlhttp.open("GET", "ajax_info.txt", true);
    xmlhttp.send();
}


//var r = new XMLHttpRequest();
//r.open("POST", "webservice", true);
//r.onreadystatechange = function () {
//    if (r.readyState != 4 || r.status != 200) return;
//    console.log(r.responseText);
//};
//r.send("a=1&b=2&c=3");