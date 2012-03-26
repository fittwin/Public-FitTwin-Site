/* This file contains all javascript functions for fittwin.com */

/**
 * jQuery Cookie plugin
 *
 * Copyright (c) 2010 Klaus Hartl (stilbuero.de)
 * Dual licensed under the MIT and GPL licenses:
 * http://www.opensource.org/licenses/mit-license.php
 * http://www.gnu.org/licenses/gpl.html
 *
 */
/* 
jQuery.cookie = function (key, value, options) {

    // key and at least value given, set cookie...
    if (arguments.length > 1 && String(value) !== "[object Object]") {
        options = jQuery.extend({}, options);

        if (value === null || value === undefined) {
            options.expires = -1;
        }

        if (typeof options.expires === 'number') {
            var days = options.expires, t = options.expires = new Date();
            t.setDate(t.getDate() + days);
        }

        value = String(value);

        return (document.cookie = [
            encodeURIComponent(key), '=',
            options.raw ? value : encodeURIComponent(value),
            options.expires ? '; expires=' + options.expires.toUTCString() : '', // use expires attribute, max-age is not supported by IE
            options.path ? '; path=' + options.path : '',
            options.domain ? '; domain=' + options.domain : '',
            options.secure ? '; secure' : ''
        ].join(''));
    }

    // key and possibly options given, get cookie...
    options = value || {};
    var result, decode = options.raw ? function (s) { return s; } : decodeURIComponent;
    return (result = new RegExp('(?:^|; )' + encodeURIComponent(key) + '=([^;]*)').exec(document.cookie)) ? decode(result[1]) : null;
};
*/
/*
$(function(){
   var GETZIP = {
      getLocation: function(){
         $('#edit-distance-postal-code').val('searching...');
         if(navigator.geolocation){
            navigator.geolocation.getCurrentPosition(GETZIP.getZipCode, GETZIP.error, {timeout: 7000});//cache it for 10 minutes
         }else{
            GETZIP.error('Geo location not supported');
         }
      },
      index: 0,
      error: function(msg) {
         if(msg.code){
            //this is a geolocation error
            switch(msg.code){
            case 1:
               $("#edit-distance-postal-code").val('Permission Denied').fadeOut().fadeIn();
               break;
            case 2:
               $("#edit-distance-postal-code").val('Position Unavailable').fadeOut().fadeIn();
               break;
            case 3:
               GETZIP.index++;
               $("#edit-distance-postal-code").val('Timeout... Trying again (' + GETZIP.index + ')').fadeOut().fadeIn();
               navigator.geolocation.getCurrentPosition(GETZIP.getZipCode, GETZIP.error, {timeout: 7000});
               break;
            default:
               //nothing
            }
         }else{
            //this is a text error
            $('#edit-distance-postal-code').val(msg).addClass('failed');
         }
 
      },
 
      getZipCode: function(position){
         if ($.cookie('user-zip')) { return; }
         var position = position.coords.latitude + "," + position.coords.longitude;
         $.getJSON('proxy.php',{
            path : "http://maps.google.com/maps/api/geocode/json?latlng="+position+"&sensor=false",
            type: "application/json"
         }, function(json){
            //Find the zip code of the first result
            if(!(json.status == "OK")){
               GETZIP.error('Zip Code not Found');
               return;
            }
            var found = false;
            $(json.results[0].address_components).each(function(i, el){
               if($.inArray("postal_code", el.types) > -1){
                  $("#edit-distance-postal-code").val(el.short_name);
                  $.cookie("user-zip", el.short_name);
                  $("#views-exposed-form-facility-proximity-view-page-1").submit();
                  found = true;
                  return;
               }
            });
            if(!found){
               GETZIP.error('Zip Code not Found');
            }
         });
      }
   }
   var zip = $.cookie("user-zip");
   if (zip) {
        $("#edit-distance-postal-code").val(zip);
        //$("#views-exposed-form-facility-proximity-view-page-1").submit();
   } else {
        GETZIP.getLocation();
   }
});
*/
$(document).ready(function() {
    //copy the second select, so we can easily reset it
/*  if ($('#edit-field-product-facility-nid-nid')) {
    var value = $('#edit-field-product-facility-nid-nid').val();
    var selectClone = $('#edit-field-facility-location-nid-nid').clone();
    $('#edit-field-facility-location-nid-nid').html(selectClone.html())
    $("#edit-field-facility-location-nid-nid option[value!='" + value + "']").remove();
    $('#edit-field-product-facility-nid-nid').change(function() {
        var val = parseInt($(this).val());
        //reset the second select on each change
        $('#edit-field-facility-location-nid-nid').html(selectClone.html())
            $("#edit-field-facility-location-nid-nid option[value!='" + val + "']").remove();
    });
  }*/
});
