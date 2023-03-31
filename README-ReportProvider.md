# REDCap UI Tweaker: Report Provider Guide

Other modules can supply their own report details to the REDCap UI Tweaker module, to be displayed
on the reports simplified view. For each report, the following details must be supplied:

* **title** string, title/name of the report
* **type** string, type of the report, this could be e.g. *SQL* if the report runs a direct database
  query, *Gantt* if the report renders a Gantt chart, or something else entirely
* **description** string, a user supplied description of the report
* **permissions** string, details of who can view/edit/download etc. the report
* **definition** string, e.g. report fields, SQL query
* **options** string, any additional report options which have been set


## Example usage

The code to supply report details should be placed within the `redcap_every_page_before_render`
function. Call the `addCustomReport` function once per report. It is recommended that you use the
`areCustomReportsExpected` function to test for when the report details need to be supplied.

```php
if ( $this->isModuleEnabled('redcap_ui_tweaker') )
{
	$UITweaker = \ExternalModules\ExternalModules::getModuleInstance('redcap_ui_tweaker');
	if ( $UITweaker->areCustomReportsExpected() )
	{
		$UITweaker->addCustomReport( [
		                              'title' => 'My Example Report',
		                              'type' => 'SQL',
		                              'description' => 'A report that is just for testing.',
		                              'permissions' => "View: Admin\nDownload: Admin",
		                              'definition' => "SELECT 'test'",
		                              'options' => 'Result type: Normal dataset'
		                            ] );
	}
}
```