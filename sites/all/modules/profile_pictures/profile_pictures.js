
$(function() {
  f = function(obj) {
    $('.profile_pictures_max_dims').css('display', (obj.value == '') ? 'block' : 'none');
  }
  obj = $('select.profile_pictures_imagecache_preset');
  obj.each(function() { f(this); });
  obj.change(function() { f(this); });
});
