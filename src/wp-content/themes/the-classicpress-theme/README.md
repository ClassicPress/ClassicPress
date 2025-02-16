# The ClassicPress Theme

The ClassicPress Theme is the default ClassicPress theme and is based on the theme used on the ClassicPress website at https://www.classicpress.net. The ClassicPress Theme is designed to be used in three ways:
- as is, with customizations made using the Additional CSS box in the customizer and/or via plugins;
- as a starter theme, where you customize any of the code as you wish, provided that you also rename the theme and the directory in which it resides so that it does not get accidentally overwritten; or
- as a parent theme, which you customize by creating a child theme.

Please note that using the theme as-is or as a parent theme will mean that the theme may be updated from time to time alongside a ClassicPress update. This will not happen if you use it as a starter theme and re-name it as indicated above.

## Special Features
The ClassicPress Theme comes with one accessible menu. It automatically becomes a mobile menu on narrow screens.

The theme enables widgets to be added to a right sidebar, while specifying one sidebar for posts (the Blog Sidebar) and another for pages (the Main Sidebar).

The theme also enables widgets to be added to the homepage hero section and the footer.

The homepage hero section makes it possible to display a custom header image as well, that can be set in the Customizer.

The ClassicPress Theme also comes with a ready-made template for Frequently Asked Questions. To make use of this, create a custom post type for FAQs, register the post type as 'faq', and specify a URL for its archive (e.g. 'has_archive' => 'faqs' ). The theme will then display your FAQs using accessible disclosure widgets. Just remember to create a Custom Link to the FAQs in your menu!

## Changelog
= Version 1.1.0
* New: homepage hero section (header image and widget area)
* New: footer widget area
* Removed: hardcoded custom font (in favour of system font)
* Removed: footer menu and static CP-specific footer content
* File footer.php: new CSS-classes that will replace CP-specific ones ('classic', 'cplegal', 'cpcopyright', 'cppolicy')
* For users with custom footer styling: the CP-specific CSS-classes will be removed in the future
* Fully updated stylesheet
* Many minor changes in code

= Version 1.0.0
* Initial release
