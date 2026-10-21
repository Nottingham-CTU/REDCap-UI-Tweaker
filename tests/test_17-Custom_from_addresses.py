# Generated from Selenium IDE
# Test name: t17 - Custom from addresses
import pytest
import time
import json
from selenium import webdriver
from selenium.webdriver.common.action_chains import ActionChains
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.support import expected_conditions
from selenium.webdriver.support.wait import WebDriverWait

class Test_17_Custom_from_addresses:
  def setup_method(self, method):
    self.driver = self.selectedBrowser
    self.vars = {}
  def teardown_method(self, method):
    self.driver.quit()

  def test_17_Custom_from_addresses(self):
    self.driver.get("http://127.0.0.1/")
    self.driver.find_element(By.LINK_TEXT, "My Projects").click()
    assert len(self.driver.find_elements(By.XPATH, "//*[@id=\"table-proj_table\"][contains(.,'REDCap UI Tweaker Test')]")) > 0
    self.driver.find_element(By.CSS_SELECTOR, "a[href$=\"ControlCenter/index.php\"]").click()
    self.driver.find_element(By.CSS_SELECTOR, "a[href$=\"ExternalModules/manager/control_center.php\"]").click()
    WebDriverWait(self.driver, 30).until(expected_conditions.presence_of_element_located((By.CSS_SELECTOR, "tr[data-module=\"redcap_ui_tweaker\"] button.external-modules-configure-button")))
    self.driver.find_element(By.CSS_SELECTOR, "tr[data-module=\"redcap_ui_tweaker\"] button.external-modules-configure-button").click()
    WebDriverWait(self.driver, 30).until(expected_conditions.presence_of_element_located((By.CSS_SELECTOR, "[name=\"custom-alert-sender\"]")))
    self.driver.execute_script("sessionStorage.setItem('test-savedsetting',JSON.stringify([$('[name=\"custom-alert-sender\"]').prop('checked'),$('[name=\"custom-alert-sender-regex\"]').val()]))")
    None if (element := self.driver.find_element(By.CSS_SELECTOR, "[name=\"custom-alert-sender\"]")).is_selected() else element.click()
    self.driver.find_element(By.CSS_SELECTOR, "[name=\"custom-alert-sender-regex\"]").click()
    self.driver.find_element(By.CSS_SELECTOR, "[name=\"custom-alert-sender-regex\"]").send_keys(".+@test\\.email\\.example\\.com$")
    self.driver.find_element(By.CSS_SELECTOR, "#external-modules-configure-modal .modal-footer .save").click()
    time.sleep(2)
    self.driver.find_element(By.LINK_TEXT, "My Projects").click()
    self.driver.find_element(By.LINK_TEXT, "REDCap UI Tweaker Test").click()
    self.driver.find_element(By.CSS_SELECTOR, "a[href*=\"AlertsController:setup\"]").click()
    time.sleep(0.5)
    self.driver.find_element(By.ID, "addNewAlert").click()
    self.driver.find_element(By.ID, "alert-trigger3").click()
    self.driver.execute_script("$('#alert-condition').val('1=2')")
    self.driver.find_element(By.NAME, "email-from").find_element(By.XPATH, "(descendant::option)[. = 'Enter a different email address...']").click()
    self.driver.find_element(By.CSS_SELECTOR, ".ui-dialog [type=\"text\"]").click()
    self.driver.find_element(By.CSS_SELECTOR, ".ui-dialog [type=\"text\"]").send_keys("example1@test.email.example.com")
    self.driver.find_element(By.CSS_SELECTOR, ".ui-dialog button:not(.ui-dialog-titlebar-close)").click()
    assert self.driver.find_element(By.NAME, "email-from").get_attribute("value") == "example1@test.email.example.com"
    self.driver.find_element(By.NAME, "email-to-freeform").click()
    self.driver.find_element(By.NAME, "email-to-freeform").send_keys("example2@test.email.example.com")
    self.driver.find_element(By.NAME, "email-subject").click()
    self.driver.find_element(By.NAME, "email-subject").send_keys("Test")
    self.driver.execute_script("tinymce.activeEditor.setContent('Test')")
    self.driver.execute_script("$('#south').remove()")
    self.driver.find_element(By.ID, "btnModalsaveAlert").click()
    WebDriverWait(self.driver, 30).until(expected_conditions.presence_of_element_located((By.ID, "south")))
    assert len(self.driver.find_elements(By.CSS_SELECTOR, "a[href=\"mailto:example1@test.email.example.com\"]")) > 0
    self.driver.find_element(By.CSS_SELECTOR, "a[href*=\"online_designer.php\"]").click()
    self.driver.find_element(By.ID, "autoInviteBtn-demographics").click()
    self.driver.find_element(By.ID, "email_sender").find_element(By.XPATH, "(descendant::option)[. = 'Enter a different email address...']").click()
    self.driver.find_element(By.CSS_SELECTOR, ".ui-dialog [type=\"text\"]").click()
    self.driver.find_element(By.CSS_SELECTOR, ".ui-dialog [type=\"text\"]").send_keys("example3@test.email.example.com")
    self.driver.find_element(By.CSS_SELECTOR, ".ui-dialog button:not(.ui-dialog-titlebar-close)").click()
    assert self.driver.find_element(By.ID, "email_sender").get_attribute("value") == "example3@test.email.example.com"
    self.driver.find_element(By.XPATH, "//div[@aria-describedby='popupSetUpCondInvites']//button[contains(.,'Cancel')]").click()
    self.driver.find_element(By.CSS_SELECTOR, "button[onclick*=\"Surveys/edit_info.php\"][onclick*=\"page=demographics\"]").click()
    self.driver.find_element(By.ID, "confirmation_email_enable").find_element(By.CSS_SELECTOR, "*[value='1']").click()
    self.driver.find_element(By.ID, "confirmation_email_from").find_element(By.XPATH, "(descendant::option)[. = 'Enter a different email address...']").click()
    self.driver.find_element(By.CSS_SELECTOR, ".ui-dialog [type=\"text\"]").click()
    self.driver.find_element(By.CSS_SELECTOR, ".ui-dialog [type=\"text\"]").send_keys("example4@test.email.example.com")
    self.driver.find_element(By.CSS_SELECTOR, ".ui-dialog button:not(.ui-dialog-titlebar-close)").click()
    assert self.driver.find_element(By.ID, "confirmation_email_from").get_attribute("value") == "example4@test.email.example.com"
    self.driver.find_element(By.CSS_SELECTOR, "a[href$=\"ControlCenter/index.php\"]").click()
    self.driver.find_element(By.CSS_SELECTOR, "a[href$=\"ExternalModules/manager/control_center.php\"]").click()
    WebDriverWait(self.driver, 30).until(expected_conditions.presence_of_element_located((By.CSS_SELECTOR, "tr[data-module=\"redcap_ui_tweaker\"] button.external-modules-configure-button")))
    self.driver.find_element(By.CSS_SELECTOR, "tr[data-module=\"redcap_ui_tweaker\"] button.external-modules-configure-button").click()
    WebDriverWait(self.driver, 30).until(expected_conditions.presence_of_element_located((By.CSS_SELECTOR, "[name=\"custom-alert-sender\"]")))
    self.driver.execute_script("$('[name=\"custom-alert-sender-regex\"]').val(JSON.parse(sessionStorage.getItem('test-savedsetting'))[1]);$('[name=\"custom-alert-sender\"]').prop('checked',JSON.parse(sessionStorage.getItem('test-savedsetting'))[0]).click().click()")
    self.driver.find_element(By.CSS_SELECTOR, "#external-modules-configure-modal .modal-footer .save").click()
    time.sleep(2)
