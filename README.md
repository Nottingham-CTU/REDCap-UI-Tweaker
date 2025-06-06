# REDCap UI Tweaker
This module provides a selection of options to adjust the REDCap user interface.


## Project-level configuration options

### Hide 'Unverified' option on data entry forms
This will remove the *Unverified* option from the form status field, leaving only *Incomplete* and
*Complete*.

### Require a reason for change for forms marked 'complete' only
This will prompt users for a reason for change whenever editing a form that was previously marked as
complete. Note that a reason for change will be required if a form is changed from complete to
incomplete, but further changes will not then require a reason for change until the form has been
marked as complete again.

This setting, if enabled, will override the REDCap setting in Project Setup -> Additional
Customizations.

### Prevent selecting the 'lock this instrument' option being treated as a data change
This will prevent the 'lock this instrument' checkbox from counting as a data change when it is
toggled on a data entry form. This will prevent warnings about unsaved data and prompts for a reason
for change if no other data has been modified on the form.

### Form submit options
If custom submit options are enabled, this setting allows the submit options to be set for the
project. This will override any system setting, but can itself be overridden by the @SAVEOPTIONS
action tag.

To use this setting, enter a comma separated list of submit options:
* record &ndash; *Save & Exit Form*
* continue &ndash; *Save & Stay*
* nextinstance &ndash; *Save & Add New Instance*
* nextform &ndash; *Save & Go To Next Form*
* nextrecord &ndash; *Save & Go To Next Record*
* exitrecord &ndash; *Save & Exit Record*
* compresp &ndash; *Save & Mark Survey as Complete*

To use the action tag, enter `@SAVEOPTIONS=` followed by a comma separated list of submit options.
This is applied on a per-form basis by using the action tag on any of the fields on that form.
The first @SAVEOPTIONS action tag encountered on the form will be used, excluding fields hidden by
branching logic when the form loads.

### Redirect from the Project Home page to this URL
If set, this will cause the *Project Home* page to redirect to the specified URL. Either an absolute
URL (starting with `http://` or `https://`) or a relative URL can be used. Relative URLs are
relative to the REDCap version directory.

* Example absolute URL: `https://example.com`
* Example relative URL: `DataEntry/record_status_dashboard.php?pid=*`

If the URL is a relative URL and it contains `pid=*`, the `*` will be replaced with the current
project ID. This does not apply to absolute URLs.

Only administrators can set the URL to an absolute URL or a relative URL for a different project
(where `pid` has a value other than `*`).

### Report namespaces for specific roles
This setting allows namespaces or sandboxes to be set up for reports. User roles can be entered so
that those roles can create, edit and delete reports *only in the namespace*. For this to work
correctly, the specified roles *should not* have the reports privilege set in User Rights.

### If custom logo/name is displayed
Within the project settings in the REDCap control center, there is an option to display the custom
logo and institution name at the top of all project pages. If that option is set to yes, this
setting allows it to be further adjusted so only the logo or only the institution name is displayed.

This setting is only available to administrators.

### Form navigation fix
When submitting a form and navigating away from the form (e.g. *Save & Go To Next Form*), a
*Save & Stay* will be performed first if this option is enabled. This can be used to work around
issues in REDCap or other modules if data needs to be saved first for the form navigation to work
correctly. This setting will not normally be required and should only be enabled if the next form
loaded after saving a form is not the expected form.

This setting is only available to administrators.


## System-wide configuration options

### Order / placement of field types
This setting defines a custom ordering of the field types in the add/edit field window. Enter the
field types as a comma-separated list of numbers, where each number corresponds to the field type
listed below.

1. Text box
2. Notes box
3. Calculated Field
4. Multiple Choice (drop down list)
5. Multiple choice (radio buttons)
6. Checkboxes
7. Yes-No
8. True-False
9. Signature
10. File Upload
11. Slider / Visual Analog Scale

The field types you specify will be shown at the top of the field type list, under the heading
**Common Field Types**. This will be followed by the **Headers and Descriptions** section (which
contains *Begin New Section* and *Descriptive Text*). Unspecified field types will be shown at the
bottom of the list, under the heading **Other Field Types**.

To adjust the order of field types listed under **Other Field Types**, specify the common field
types and other field types as two comma-separated lists, separated by a pipe (|) character. Any
unspecified field types will then be listed at the bottom of the other field types, under the
specified other field types.

If this setting is blank or invalid, the REDCap default field types list will be used.

### Set required status on new fields
This will set the **required** option on new fields to *yes*. For standard fields, the required
option will be set to *yes* for all field types except descriptive and calculated fields for which
the option will be set to *no*. For matrix fields, the required option will be ticked when each
field is added, but can be unticked once the field label and name have been set.

### Predefined field annotations
Enter field annotations here (one per line) for them to be provided in a drop down list on the
add/edit field form for easy selection.

For example, you could use the categories from the
[DataCat project](https://trialsjournal.biomedcentral.com/articles/10.1186/s13063-020-04388-x/tables/1):

```
Primary Outcome
Primary Outcome Not Primary Analysis
Secondary Outcome
Other Outcome
Core Outcome
Health Economics
Participant Identifier
Randomisation
Eligibility
Demographics
Data Management
Safety Data
Regulatory Data
Compliance Data
Process Outcome
Miscellaneous
```

### Always show full annotations in the online designer
If enabled, full field annotations will be shown at the bottom of each field in the online designer
instead of just the action tags.

### Enable @SQLDESCRIPTIVE action tag
Allow use of the **@SQLDESCRIPTIVE** action tag for SQL fields, which will use the option label from
the selected option in an SQL field as descriptive text and render the field like a descriptive
field. This allows HTML content to be generated on the page using a database query. You will
probably want to pair this action tag with @DEFAULT or @SETVALUE/@PREFILL to select the appropriate
option.

As REDCap may strip HTML from option labels, for best results try returning data from the database
in URL-encoded or base64 format, which can be indicated by prefixing the data as follows:
* raw: &ndash; no encoding
* url: &ndash; URL-encoded
* b64: &ndash; base64 encoded
* if not prefixed, then assume raw (no encoding)

### Enable @SQLCHECKBOX action tag
Allow use of the **@SQLCHECKBOX** action tag for SQL fields, which will replace the drop-down list
with a checkbox for each option similar to a checkbox field.

When writing an SQL query for use with the `@SQLCHECKBOX` action tag, ensure that the returned
column names are `code` and `label`, otherwise the query will not function. Also note that the
`@SQLCHECKBOX` action tag does not work with the `@IF` action tag.

For best results, the SQL query should return all possible options outside of a record context<br>
(where \[record-name\] = '') and only limit to a subset (if required) within a record context.

Due to the field being based upon the SQL drop down field type, the module has to amend the query to
return all the individual options plus all the combinations which are in use at a given time as
additional options. If many different combinations have been selected for various records this may
have an impact on performance as all these combinations will have to be loaded for the field. The
maximum number of options for an `@SQLCHECKBOX` field is therefore limited to 60.

*The previous implementation of* `@SQLCHECKBOX` *where the tag is applied to checkbox fields is now
deprecated and will be removed in a future version of this module.*

### Static form variable names
This option will allow the form variable name (as used in the \*_complete variable) to be set
explicitly when forms are created, and remain unchanged even when projects are in development
status.

### Use version fields
When a form is created, automatically place a form version field at the top of the form. This will
be a multiple choice field with a default value of `1.0`.

Static form variable names must be enabled in order to use this feature.

### Version field default annotation/action tags
Set the default action tags used on version fields when they are created. If this has not been set,
it will default to `@HIDDEN-SURVEY`.

The action tags `@DEFAULT='1.0' @NOMISSING` will always be appended to these action tags and don't
need to be included.

### Use form name prefix when creating fields
This will populate field names with a prefix based on the form name when new fields are created.

Static form variable names must be enabled in order to use this feature.

### Preserve form labels in instrument zips
This will save the form name label into the data dictionary prior to instrument download, and load
the name from the data dictionary following an instrument upload.

### Enable the Data Resolution Workflow on new projects
This option will enable the Data Resolution Workflow instead of the Field Comment Log on new
projects.

### Default missing data codes
This will set the missing data codes on new projects to the codes defined here.

### Require a reason for change on new projects
This option will enable the reason for change prompts on new projects. If enabled, it can either be
using REDCap standard functionality (prompts for all changes), or only for forms which have
previously been marked 'complete'.

### Show the fields that have changed on the change reason popup
This option will amend the reason for change prompt so that all the fields which have been changed
will be listed along with the old and new values. This can be useful for reminding users of the
changes they have made when writing the reason.

### Prevent the 'lock this instrument' option being treated as a data change
This option will default new projects to not treat toggling the 'lock this instrument' checkbox as
a data change. It can still be enabled/disabled on individual projects as required.

### Default data quality rules to execute in real time
Automatically selects the 'execute in real time' checkbox for new data quality rules on the data
quality rules page.

### Custom data quality notification header text
This will override the text displayed in the header/title of the popup notification when a record is
saved with data which violates data quality rules.

### Custom data quality notification text
This will override the text displayed within the popup notification when a record is saved with data
which violates data quality rules. This can be set separately for when data resolution workflow is
*not* enabled and when it *is* enabled.

### Hide equations on the data quality notification
When real-time execution of data quality rules triggers the popup notification of rule violations,
the rule logic code will be hidden by default. The user will have to click a 'show/hide logic' link
to toggle the visibility of logic code.

### Use alternate status icons
Replaces some of the record status icons so they can be more easily distinguished by people with
colour vision deficiency.

### Show the user's role below their username
If the user has been added to a role within a project, show their role below their username.

### Hide 'contact administrator' links
Hides one or both of the 'contact REDCap administrator' links shown on project pages.

### Hide 'suggest a new feature' link
Hides the 'suggest a new feature' link shown on project pages.

### Always show autofill option in development status
If the project is in development status, or the server is set as a development server, then the
option to autofill the forms will be shown to all users.

### Submit options
On a REDCap data entry form, there are several options available when submitting.
The **Save & Exit Form** option is always displayed next to a button/list combo of options which can
include the following:

* Save & Stay
* Save & Add New Instance
* Save & Go To Next Form
* Save & Exit Record
* Save & Go To Next Record

The default REDCap behaviour will order this list depending on which options the user has used most
recently (if any). This setting allows manipulating this list of options in one of two ways.

* Remove **Save & Go To Next Record**
* Only display a defined set of options, in the specified order (see the custom submit options for
  the format)

Note that some of the options might not be displayed on a particular data entry form if they are not
relevant.

### Allow custom submit options
Allow use of the **@SAVEOPTIONS** action tag to specify the options for submitting a data entry form.
This is applied on a per-form basis by using the action tag on any of the fields on that form.

If custom submit options are enabled, the submit options can also be set project-wide in the module
project settings (in the same format as for the action tag). If a @SAVEOPTIONS action tag is used,
it will override the project-wide setting. The @SAVEOPTIONS action tag and the project-wide setting
will each override the system-wide setting.

### Provide 'lock blank form' option
Users with the privilege for locking individual forms will see an extra option on forms which are
blank (appear with a grey status icon on the dashboard), so they can lock the form without saving
any data on it and thus retaining its blank status.

### Remember the selection of 'all status types' on the record status dashboard
If a user selects the 'all status types' option on the record status dashboard, their selection will
be remembered and auto-selected when they subsequently view the dashboard. This will apply per-user
and across all projects.

### Additional login page logo URL
Specify the URL of an image file to include it as an additional logo on the login page (on the top
right of the page, opposite the REDCap logo).

### Show 'My Projects' in alphabetical order
If this option is enabled, the list of projects on the 'My Projects' page will be shown in
alphabetical order by default. Any custom folders the user has defined will be retained, the
projects will be sorted within the folders.

### Redirect users with one project to that project
If this option is enabled, users with only one project will be redirected to that project the first
time they load the *REDCap Home* page or the *My Projects* page following login. If the
*My Projects* page is the first page shown after login, the user is immediately redirected to their
project.

### Enable versionless URLs
If enabled, this will instruct the user's web browser to strip the version number (the _vX.X.X part)
from the REDCap URL. This should ensure that bookmarks are always saved without the version number.
This can make it easier to redirect users from their saved links or bookmarks to the current version
if old REDCap code is removed from the web server.

*In order to benefit from this feature, a redirect will need to be set up on the web server so that
any attempt to load a page without the version number will be automatically redirected to the URL
with the current REDCap version. If this feature is enabled without such a redirect in place, it
can result in broken links/bookmarks being saved.*

*Enabling versionless URLs may introduce bugs and other unexpected behaviour, especially in other
external modules. It is recommended that the regular expression matching is used so that versionless
URLs are only activated where they are most useful.*

If matching or excluding URLs based on regular expressions, place each regular expression on its own
line. The portion of the URL which will be matched to the regular expression is everything after the
slash which follows the redcap version directory. Slashes do not need to be escaped in the regular
expressions for this setting.

### Allow custom from addresses in alerts/ASIs
Provides an option when adding an alert to enter an email from address that is not provided in the
dropdown list. If this option is enabled, use the *regular expression to validate custom from
addresses* option to restrict the addresses which can be used (e.g. to limit to your own domain).

### Alerts simplified view
If enabled, a button will be added to the alerts and notifications page to show a simplified view.
This will provide an overview of the alerts as a table. Once the simplified view is shown, a button
to select the table is displayed to make it easier to copy the table e.g. for use in documentation.

Other modules can add information to the alerts simplified view. If you are a module developer, see
the [alert provider guide](README-AlertProvider.md) for more information.

### Codebook simplified view
If enabled, a button will be added to the codebook page to show a simplified view. This will hide
any buttons and icons from the codebook table, remove the field number column, simplify the
instrument headings and move the field annotations to a separate column. Once the simplified view
is shown, a button to select the table is displayed to make it easier to copy the table e.g. for use
in documentation.

### External modules simplified view
If enabled, a button will be added to the external modules page (on projects only) to show a
simplified view. This will provide a simple table of the external modules and their settings. This
simplified view can be enabled only for administrators, so that if there are any modules with
sensitive settings these are not made available to regular users. Once the simplified view is shown,
a button to select the table is displayed to make it easier to copy the table e.g. for use in
documentation.

If you are a module developer, you can adjust how your module settings are displayed in the external
modules simplified view, see the [external module settings provider guide](README-ExtModProvider.md)
for more information.

### Data quality rules simplified view
If enabled, a button will be added to the data quality rules page to show a simplified view. This
will provide a simple table of the data quality rules, showing a unique ID number, the rule name,
the rule logic, and whether real-time execution is enabled. Once the simplified view is shown, a
button to select the table is displayed to make it easier to copy the table e.g. for use in
documentation.

### Instrument mapping simplified view
If enabled, a button will be added to the designate instruments for events page to show a simplified
view. This will provide a simple table of which instruments are on each event, in which all arms are
shown at once. Once the simplified view is shown, a button to select the table is displayed to make
it easier to copy the table e.g. for use in documentation.

### Reports simplified view
If enabled, a button will be added to the reports page to show a simplified view. This will provide
an overview of the reports as a table. Once the simplified view is shown, a button to select the
table is displayed to make it easier to copy the table e.g. for use in documentation.

Other modules can add information to the reports simplified view. If you are a module developer, see
the [report provider guide](README-ReportProvider.md) for more information.

### User rights simplified view
If enabled, a button will be added to the user rights page to show a simplified view.
This will provide an overview of the user rights as a table showing the rights granted to each role.
This includes the basic rights and the data viewing/export rights for each data entry instrument.
Once the simplified view is shown, a button to select the table is displayed to make it easier to
copy the table e.g. for use in documentation.
