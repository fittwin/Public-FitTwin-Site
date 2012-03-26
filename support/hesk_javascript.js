/*******************************************************************************
*  Title: Help Desk Software HESK
*  Version: 2.2 from 9th June 2010
*  Author: Klemen Stirn
*  Website: http://www.hesk.com
********************************************************************************
*  COPYRIGHT AND TRADEMARK NOTICE
*  Copyright 2005-2010 Klemen Stirn. All Rights Reserved.
*  HESK is a registered trademark of Klemen Stirn.

*  The HESK may be used and modified free of charge by anyone
*  AS LONG AS COPYRIGHT NOTICES AND ALL THE COMMENTS REMAIN INTACT.
*  By using this code you agree to indemnify Klemen Stirn from any
*  liability that might arise from it's use.

*  Selling the code for this program, in part or full, without prior
*  written consent is expressly forbidden.

*  Using this code, in part or full, to create derivate work,
*  new scripts or products is expressly forbidden. Obtain permission
*  before redistributing this software over the Internet or in
*  any other medium. In all cases copyright and header must remain intact.
*  This Copyright is in full effect in any country that has International
*  Trade Agreements with the United States of America or
*  with the European Union.

*  Removing any of the copyright notices without purchasing a license
*  is expressly forbidden. To remove HESK copyright notice you must purchase
*  a license for this script. For more information on how to obtain
*  a license please visit the page below:
*  https://www.hesk.com/buy.php
*******************************************************************************/

function hesk_insertTag(tag) {
var text_to_insert = '%%'+tag+'%%';
hesk_insertAtCursor(document.form1.msg, text_to_insert);
document.form1.message.focus();
}

function hesk_insertAtCursor(myField, myValue) {
if (document.selection) {
myField.focus();
sel = document.selection.createRange();
sel.text = myValue;
}
else if (myField.selectionStart || myField.selectionStart == '0') {
var startPos = myField.selectionStart;
var endPos = myField.selectionEnd;
myField.value = myField.value.substring(0, startPos)
+ myValue
+ myField.value.substring(endPos, myField.value.length);
} else {
myField.value += myValue;
}
}

function hesk_changeAll() {
  var d = document.form1;
  var setTo = d.checkall.checked ? true : false;

  for (var i = 0; i < d.elements.length; i++)
  {
    if(d.elements[i].type == 'checkbox' && d.elements[i].name != 'checkall')
    {
     d.elements[i].checked = setTo;
    }
  }
}

function hesk_attach_disable(ids) {
 for($i=0;$i<ids.length;$i++) {
      if (ids[$i]=='c11'||ids[$i]=='c21'||ids[$i]=='c31'||ids[$i]=='c41'||ids[$i]=='c51') {
            document.getElementById(ids[$i]).checked=false;
      }
      document.getElementById(ids[$i]).disabled=true;
 }
}

function hesk_attach_enable(ids) {
 for($i=0;$i<ids.length;$i++) {
      document.getElementById(ids[$i]).disabled=false;
 }
}

function hesk_attach_toggle(control,ids) {
 if (document.getElementById(control).checked) {
     hesk_attach_enable(ids);
 } else {
     hesk_attach_disable(ids);
 }
}

function hesk_window(PAGE,HGT,WDT)
{
 var HeskWin = window.open(PAGE,"Hesk_window","height="+HGT+",width="+WDT+",menubar=0,location=0,toolbar=0,status=0,resizable=1,scrollbars=1");
 HeskWin.focus();
}

function hesk_toggleLayerDisplay(nr) {
        if (document.all)
                document.all[nr].style.display = (document.all[nr].style.display == 'none') ? 'block' : 'none';
        else if (document.getElementById)
                document.getElementById(nr).style.display = (document.getElementById(nr).style.display == 'none') ? 'block' : 'none';
}

function hesk_confirmExecute(myText) {
         if (confirm(myText))
         {
          return true;
         }
         return false;
}

function hesk_deleteIfSelected(myField,myText) {
         if(document.getElementById(myField).checked)
         {
          return hesk_confirmExecute(myText);
         }
}

function hesk_rate(url,element_id)
{
        if (url.length==0)
        {
                return false;
        }

        var element = document.getElementById(element_id);

        xmlHttp=GetXmlHttpObject();
        if (xmlHttp==null)
        {
                alert ("Your browser does not support AJAX!");
                return;
        }

        xmlHttp.open("GET",url,true);

        xmlHttp.onreadystatechange = function()
        {
         if (xmlHttp.readyState == 4 && xmlHttp.status == 200)
         {
          element.innerHTML = xmlHttp.responseText;
         }
        }

        xmlHttp.send(null);
}

function stateChanged()
{
        if (xmlHttp.readyState==4)
        {
                document.getElementById("rating").innerHTML=xmlHttp.responseText;
        }
}

function GetXmlHttpObject()
{
        var xmlHttp=null;
        try
        {
                // Firefox, Opera 8.0+, Safari
                xmlHttp=new XMLHttpRequest();
        }
        catch (e)
        {
                // Internet Explorer
                try
                {
                        xmlHttp=new ActiveXObject("Msxml2.XMLHTTP");
                }
                catch (e)
                {
                        xmlHttp=new ActiveXObject("Microsoft.XMLHTTP");
                }
        }
        return xmlHttp;
}

var heskKBquery = '';
var heskKBfailed = false;

function hesk_suggestKB()
{
 var d = document.form1;
 var s = d.subject.value;
 var m = d.message.value;
 var element = document.getElementById('kb_suggestions');

 if (s != '' && m != '' && (heskKBquery != s + " " + m || heskKBfailed == true) )
 {
  element.style.display = 'block';
  var params = "p=1&q=" + escape(s + " " + m);
  heskKBquery = s + " " + m;

   xmlHttp=GetXmlHttpObject();
   if (xmlHttp==null)
   {
     return;
   }

   xmlHttp.open('POST','suggest_articles.php',true);

   xmlHttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
   xmlHttp.setRequestHeader("Content-length", params.length);
   xmlHttp.setRequestHeader("Connection", "close");

   xmlHttp.onreadystatechange = function()
   {
      if (xmlHttp.readyState == 4 && xmlHttp.status == 200)
      {
       element.innerHTML = xmlHttp.responseText;
       heskKBfailed = false;
      }
      else
      {
       heskKBfailed = true;
      }
   }

   xmlHttp.send(params);
 }

 setTimeout('hesk_suggestKB();', 2000);

}

function hesk_suggestKBsearch()
{
 var d = document.searchform;
 var s = d.search.value;
 var element = document.getElementById('kb_suggestions');

 if (s != '' && (heskKBquery != s || heskKBfailed == true) )
 {
  element.style.display = 'block';
  var params = "q=" + escape(s);
  heskKBquery = s;

   xmlHttp=GetXmlHttpObject();
   if (xmlHttp==null)
   {
     return;
   }

   xmlHttp.open('POST','suggest_articles.php',true);

   xmlHttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
   xmlHttp.setRequestHeader("Content-length", params.length);
   xmlHttp.setRequestHeader("Connection", "close");

   xmlHttp.onreadystatechange = function()
   {
      if (xmlHttp.readyState == 4 && xmlHttp.status == 200)
      {
       element.innerHTML = unescape(xmlHttp.responseText);
       heskKBfailed = false;
      }
      else
      {
       heskKBfailed = true;
      }
   }

   xmlHttp.send(params);
 }

 setTimeout('hesk_suggestKBsearch();', 2000);
}

function hesk_btn(Elem, myClass)
{
        Elem.className = myClass;
}

function hesk_checkPassword(password)
{

    var numbers = "0123456789";
    var lowercase = "abcdefghijklmnopqrstuvwxyz";
    var uppercase = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
    var punctuation = "!.@$L#*()%~<>{}[]";

    var combinations = 0;

    if (hesk_contains(password, numbers) > 0) {
        combinations += 10;
    }

    if (hesk_contains(password, lowercase) > 0) {
        combinations += 26;
    }

    if (hesk_contains(password, uppercase) > 0) {
        combinations += 26;
    }

    if (hesk_contains(password, punctuation) > 0) {
        combinations += punctuation.length;
    }

    var totalCombinations = Math.pow(combinations, password.length);
    var timeInSeconds = (totalCombinations / 200) / 2;
    var timeInDays = timeInSeconds / 86400
    var lifetime = 3650;
    var percentage = timeInDays / lifetime;

    var friendlyPercentage = hesk_cap(Math.round(percentage * 100), 98);

    if (friendlyPercentage < (password.length * 5)) {
        friendlyPercentage += password.length * 5;
    }

    var friendlyPercentage = hesk_cap(friendlyPercentage, 98);

    var progressBar = document.getElementById("progressBar");
    progressBar.style.width = friendlyPercentage + "%";

    if (percentage > 1) {
        // strong password
        progressBar.style.backgroundColor = "#3bce08";
        return;
    }

    if (percentage > 0.5) {
        // reasonable password
        progressBar.style.backgroundColor = "#ffd801";
        return;
    }

    if (percentage > 0.10) {
        // weak password
        progressBar.style.backgroundColor = "orange";
        return;
    }

    if (percentage <= 0.10) {
        // very weak password
        progressBar.style.backgroundColor = "red";
        return;
    }

}

function hesk_cap(number, max) {
    if (number > max) {
        return max;
    } else {
        return number;
    }
}

function hesk_contains(password, validChars) {

    count = 0;

    for (i = 0; i < password.length; i++) {
        var char = password.charAt(i);
        if (validChars.indexOf(char) > -1) {
            count++;
        }
    }

    return count;
}