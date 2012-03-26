
====================== About =====================
  The profile_pictures module allows you to create images fields in core profile
module.

==================== Settings ====================
  You can add image field at admin/user/profile page.
Image field has all default field settings plus some specific image options,
such as: maximum file size (after resizing), maximum image dimensions.
All uploaded images will automatically resize to fit dimensions (like
user's pictures in core user module)

============= Default file saving ================
=========== Browser caching problem ==============
  All files are saving in sites/*/files/pictures (you need to create this folder
manually). If you have the Token module, refer to the 'Integration with Token'
part of this readme.
  Otherwise every file will have name like
user-[uid]-[field-name].[extension]. For example: user-1-profile_photo.jpg.
Image links will be like [filename]?[random]. For example: sites/test/files/
/pictures/user-1-profile_photo.jpg?1234567890abcdef.
  Random suffix is important, because browsers cache images with the same name.
All files will be removed when you uninstall module.

============= Integration with Token =============
  You can enable Token module (http://drupal.org/project/token) for set custom
saving paths for every field. You can use all 'user' and 'global' tokens plus:
    [profile_picture-filename]  - source file name
    [profile_picture-extension] - image file extension
    [profile_picture-name]      - untranslated field name
  This feature enables automatically when Token module is enabled. You can change
default settings on field's edit page.
  !WARNING!: default path when you enabled Token will be reset to
sites/.../files/pictures/... even if your core user profile images is saving in
the different folder.

========== Integration with ImageCache ===========
  You can use ImageCache and ImageApi modules (http://drupal.org/project/imagecache)
for addind image postprocessing options. If you enable ImageCache, you will be
able to select custom imagecache presets for pre-processing before display image
in user's profile. Also you are able to select any other imagecache preset for
each views field.

======== Integration with OnePageProfile =========
  You should patch your OnePageProfile to make it works correctly with
profile_pictures. Patch could be found in bug [#800918]
( http://drupal.org/node/800918 )
