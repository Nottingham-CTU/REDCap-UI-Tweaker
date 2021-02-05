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

### Use alternate status icons
Replaces some of the record status icons so they can be more easily distinguished by people with
colour vision deficiency.

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

If the URL contains `pid=*`, the `*` will be replaced with the current project ID.

This setting is only available to administrators.

### Form navigation fix
When submitting a form and navigating away from the form (e.g. *Save & Go To Next Form*), a
*Save & Stay* will be performed first if this option is enabled. This can be used to work around
issues in REDCap or other modules if data needs to be saved first for the form navigation to work
correctly.

This setting is only available to administrators.
