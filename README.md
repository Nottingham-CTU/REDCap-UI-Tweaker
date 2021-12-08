# REDCap-UI-Tweaker
This module provides a selection of options to adjust the REDCap user interface.


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
option will not be visible when creating the field, but will be visible when editing the field and
can be set to *no* at that time. For matrix fields, the required option will be ticked when each
field is added, but can be unticked once the field label and name have been set.

### Predefined field annotations
Enter field annotations here (one per line) for them to be provided in a drop down list on the
add/edit field form for easy selection.

### Enable @SQLDESCRIPTIVE action tag
Allow use of the **@SQLDESCRIPTIVE** action tag, which will use the option label from the selected
option in an SQL field as descriptive text and render the field like a descriptive field. This
allows HTML content to be generated on the page using a database query. You will probably want to
pair this action tag with @DEFAULT or @PREFILL to select the appropriate option.

As REDCap may strip HTML from option labels, for best results try returning data from the database
in URL-encoded or base64 format, which can be indicated by prefixing the data as follows:
* raw: &ndash; no encoding
* url: &ndash; URL-encoded
* b64: &ndash; base64 encoded
* if not prefixed, then assume raw (no encoding)

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
* Only display the following options, in this order
  * Save & Add New Instance
  * Save & Go To Next Form
  * Save & Stay

Note that some of the options might not be displayed on a particular data entry form if they are not
relevant.

This setting does not change the *Save & Exit Form* option, but this can be moved or removed using
the custom submit options (see below).

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

### Allow custom from addresses in alerts
Provides an option when adding an alert to enter an email from address that is not provided in the
dropdown list. If this option is enabled, use the *regular expression to validate custom from
addresses* option to restrict the addresses which can be used (e.g. to limit to your own domain).

### Use alternate status icons
Replaces some of the record status icons so they can be more easily distinguished by people with
colour vision deficiency.

### Add a 'simplified view' option to the codebook
If enabled, a button will be added to the codebook page to show a simplified view. This will hide
any buttons and icons from the codebook table, remove the field number column, simplify the
instrument headings and move the field annotations to a separate column. Once the simplified view
is shown, a button to select the table is displayed to make it easier to copy the table e.g. for use
in documentation.

### Show 'My Projects' in alphabetical order
If this option is enabled, the list of projects on the 'My Projects' page will be shown in
alphabetical order by default. Any custom folders the user has defined will be retained, the
projects will be sorted within the folders.

### Redirect users with one project to that project
If this option is enabled, users with only one project will be redirected to that project the first
time they load the *REDCap Home* page or the *My Projects* page following login. If the
*My Projects* page is the first page shown after login, the user is immediately redirected to their
project.


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
