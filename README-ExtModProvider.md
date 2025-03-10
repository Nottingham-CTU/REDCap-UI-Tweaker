# REDCap UI Tweaker: External Module Settings Provider Guide

Other modules can provide adjusted settings for use by the REDCap UI Tweaker module, to be displayed
on the external modules simplified view. Doing this will cause the simplified view to display the
adjusted settings rather than the actual settings. This can be useful for:

* Providing the settings to the simplified view in a more user-friendly format.
* Hiding sensitive details contained within the settings.
* Hiding settings which convey information which is better presented elsewhere (e.g. if your module
  provides reports or alerts, this data might be better presented in the reports or alerts
  simplified views).

There are 2 methods to provide amended settings to this module.

## Method 1: Define an exportProjectSettings() function

If your module defines an exportProjectSettings() function, the output of this function will be used
instead of the actual values stored in the database. The function should return an array of arrays,
in which the inner arrays contain the keys `key` and `value`.

```
  [
    [ 'key' => 'some-key-name-here', 'value' => 'A setting value here' ],
    [ 'key' => 'another-key-name-here', 'value' => 'Another setting value here' ],
    ...
  ]
```

This method is intended for performing settings adjustments for any exportable context, and so could
be utilised by modules other than this one.

## Method 2: Provide a settings transformation function

This supplies a callback function to the REDCap UI Tweaker module, which will be used to process the
module's settings before being presented on the external modules simplified view. Please be aware
that:

* This method completely overrides the first method, if you supply a transformation function then
  any exportProjectSettings function will not be called.
* This method applies specifically to the external modules simplified view and will not affect any
  other usage or presentation of module settings.

The code to supply a transformation function should be placed within the
`redcap_every_page_before_render` function. Call the `addExtModFunc` function once. It is
recommended that you use the `areExtModFuncExpected` function to test for when the transformation
function needs to be supplied.

The callback function should take one parameter, which is an array containing two keys: `setting`
and `value`. The function can either:

* Modify the array and return it, to provide modified setting names and/or values.
* Return true, to leave the setting as is on the simplified view.
* Return false, to hide the setting on the simplified view.

```php
if ( $this->isModuleEnabled('redcap_ui_tweaker') )
{
	$UITweaker = \ExternalModules\ExternalModules::getModuleInstance('redcap_ui_tweaker');
	if ( $UITweaker->areExtModFuncExpected() )
	{
		$UITweaker->addExtModFunc( 'my_module_directory_name',
		                           function ( $data )
		                           {
		                             if ( $data['setting'] == 'leave-this-one' )
		                             {
		                               return true;
		                             }
		                             elseif ( $data['setting'] == 'hide-this-one' )
		                             {
		                               return false;
		                             }
		                             elseif ( $data['setting'] == 'change-the-value' )
		                             {
		                               $data['value'] = 'The value is changed.';
		                               return $data;
		                             }
		                             elseif ( $data['setting'] == 'change-the-setting' )
		                             {
		                               $data['setting'] = 'The setting name is changed.';
		                               return $data;
		                             }
		                           }
		                         );
	}
}
```
