# REDCap UI Tweaker: Alert Provider Guide

Other modules can supply their own alert details to the REDCap UI Tweaker module, to be displayed
on the alerts simplified view. For each alert, the following details must be supplied:

* **enabled** boolean, true if the alert is enabled, false otherwise
* **title** string, title of the alert (optional)
* **type** string, type of the alert, this could be *Email*, *SMS* or something else entirely
* **form** string, the instrument to which the alert relates (optional)
* **trigger** string (HTML), details of the alert trigger
* **schedule** string (HTML), details of the alert schedule
* **message** string (HTML), the alert message (include details of sender/recipient as appropriate)

Note that the trigger, schedule and message fields accept data in HTML format. If user provided data
is being supplied to these fields, ensure that it has been appropriately escaped (e.g. using the
`htmlspecialchars` function).


## Example usage

The code to supply alert details should be placed within the `redcap_every_page_before_render`
function. Call the `addCustomAlert` function once per alert. It is recommended that you use the
`areCustomAlertsExpected` function to test for when the alert details need to be supplied.

```php
if ( $this->isModuleEnabled('redcap_ui_tweaker') )
{
	$UITweaker = \ExternalModules\ExternalModules::getModuleInstance('redcap_ui_tweaker');
	if ( $UITweaker->areCustomAlertsExpected() )
	{
		$UITweaker->addCustomAlert( [
		                              'enabled' => true,
		                              'title' => 'My Example Alert',
		                              'type' => 'SMS',
		                              'form' => 'Form 1',
		                              'trigger' => 'On form save',
		                              'schedule' => 'At full moon only',
		                              'message' => '<b>To:</b> 123<br>Hello'
		                            ] );
	}
}
```