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

### Redirect from the Project Home page to this URL
If set, this will cause the *Project Home* page to redirect to the specified URL. Either an absolute
URL (starting with `http://` or `https://`) or a relative URL can be used. Relative URLs are
relative to the REDCap version directory.

If the URL is a relative URL and it contains `pid=*`, the `*` will be replaced with the current
project ID. This does not apply to absolute URLs.

Only administrators can set the URL to an absolute URL or a relative URL for a different project
(where `pid` has a value other than `*`).

### If custom logo/name is displayed
Within the project settings in the REDCap control center, there is an option to display the custom
logo and institution name at the top of all project pages. If that option is set to yes, this
setting allows it to be further adjusted so only the logo or only the institution name is displayed.

This setting is only available to administrators.

### Form navigation fix
When submitting a form and navigating away from the form (e.g. *Save & Go To Next Form*), a
*Save & Stay* will be performed first if this option is enabled. This can be used to work around
issues in REDCap or other modules if data needs to be saved first for the form navigation to work
correctly.

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
Allow use of the **@SQLCHECKBOX** action tag for checkbox fields, which will dynamically replace
the checkbox options with those from a specified SQL field. The format must follow the pattern
@SQLCHECKBOX='????', in which the desired value must be the field name of an SQL field in the
project.<br>Note: Checkbox options will **not** be replaced if the form, record or project has been
locked.

For best results, the SQL query should return all possible options outside of a record context<br>
(where \[record-name\] = '') and only limit to a subset (if required) within a record context.

### Enable the Data Resolution Workflow on new projects
This option will enable the Data Resolution Workflow instead of the Field Comment Log on new
projects.

### Default missing data codes
This will set the missing data codes on new projects to the codes defined here.

### Require a reason for change on new projects
This option will enable the reason for change prompts on new projects. If enabled, it can either be
using REDCap standard functionality (prompts for all changes), or only for forms which have
previously been marked 'complete'.

### Prevent the 'lock this instrument' option being treated as a data change
This option will default new projects to not treat toggling the 'lock this instrument' checkbox as
a data change. It can still be enabled/disabled on individual projects as required.

### Default data quality rules to execute in real time
Automatically selects the 'execute in real time' checkbox for new data quality rules on the data
quality rules page.

### Custom data quality notification header text
This will override the text displayed in the header/title of the popup notification when a record is
saved with data which violates data quality rules.

### Use alternate status icons
Replaces some of the record status icons so they can be more easily distinguished by people with
colour vision deficiency.

### Show the user's role below their username
If the user has been added to a role within a project, show their role below their username.

### Hide 'contact administrator' links
Hides one or both of the 'contact REDCap administrator' links shown on project pages.

### Hide 'suggest a new feature' link
Hides the 'suggest a new feature' link shown on project pages.

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
The first @SAVEOPTIONS action tag encountered on the form will be used, excluding fields hidden by
branching logic when the form loads.

To use this action tag, enter `@SAVEOPTIONS=` followed by a comma separated list of submit options:
* record &ndash; *Save & Exit Form*
* continue &ndash; *Save & Stay*
* nextinstance &ndash; *Save & Add New Instance*
* nextform &ndash; *Save & Go To Next Form*
* nextrecord &ndash; *Save & Go To Next Record*
* exitrecord &ndash; *Save & Exit Record*

If custom submit options are enabled, the submit options can also be set project-wide in the module
project settings (in the same format as for the action tag). If a @SAVEOPTIONS action tag is used,
it will override the project-wide setting. The @SAVEOPTIONS action tag and the project-wide setting
will each override the system-wide setting.

### Remember the selection of 'all status types' on the record status dashboard
If a user selects the 'all status types' option on the record status dashboard, their selection will
be remembered and auto-selected when they subsequently view the dashboard. This will apply per-user
and across all projects.

### Show 'My Projects' in alphabetical order
If this option is enabled, the list of projects on the 'My Projects' page will be shown in
alphabetical order by default. Any custom folders the user has defined will be retained, the
projects will be sorted within the folders.

### Redirect users with one project to that project
If this option is enabled, users with only one project will be redirected to that project the first
time they load the *REDCap Home* page or the *My Projects* page following login. If the
*My Projects* page is the first page shown after login, the user is immediately redirected to their
project.

### Allow custom from addresses in alerts
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

### User rights simplified view
If enabled, a button will be added to the user rights page to show a simplified view.
This will provide an overview of the user rights as a table showing the rights granted to each role.
This includes the basic rights and the data viewing/export rights for each data entry instrument.
Once the simplified view is shown, a button to select the table is displayed to make it easier to
copy the table e.g. for use in documentation.
