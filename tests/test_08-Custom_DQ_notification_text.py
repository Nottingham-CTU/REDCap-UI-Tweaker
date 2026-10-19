# Generated from Selenium IDE
# Test name: t08 - Custom DQ notification text
import pytest
import time
import json
from selenium import webdriver
from selenium.webdriver.common.action_chains import ActionChains
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.support import expected_conditions
from selenium.webdriver.support.wait import WebDriverWait

class Test_08_Custom_DQ_notification_text:
  def setup_method(self, method):
    self.driver = self.selectedBrowser
    self.vars = {}
  def teardown_method(self, method):
    self.driver.quit()

  def test_08_Custom_DQ_notification_text(self):
    self.driver.get("http://127.0.0.1/")
    self.driver.find_element(By.LINK_TEXT, "My Projects").click()
    assert len(self.driver.find_elements(By.XPATH, "//*[@id=\"table-proj_table\"][contains(.,'REDCap UI Tweaker Test')]")) > 0
    self.driver.find_element(By.CSS_SELECTOR, "a[href$=\"ControlCenter/index.php\"]").click()
    self.driver.find_element(By.CSS_SELECTOR, "a[href$=\"ExternalModules/manager/control_center.php\"]").click()
    WebDriverWait(self.driver, 30).until(expected_conditions.presence_of_element_located((By.CSS_SELECTOR, "tr[data-module=\"redcap_ui_tweaker\"] button.external-modules-configure-button")))
    self.driver.find_element(By.CSS_SELECTOR, "tr[data-module=\"redcap_ui_tweaker\"] button.external-modules-configure-button").click()
    WebDriverWait(self.driver, 30).until(expected_conditions.presence_of_element_located((By.CSS_SELECTOR, "[name=\"dq-notify-header\"]")))
    self.driver.execute_script("sessionStorage.setItem('test-savedsetting',JSON.stringify({\"header\":$('[name=\"dq-notify-header\"]').val(),\"body\":$('[name=\"dq-notify-body\"]').val(),\"bodydrw\":$('[name=\"dq-notify-body-drw\"]').val()}))")
    self.driver.find_element(By.CSS_SELECTOR, "[name=\"dq-notify-header\"]").send_keys("CustomDQHeader")
    self.driver.find_element(By.CSS_SELECTOR, "[name=\"dq-notify-body\"]").send_keys("CustomDQBodyStandard")
    self.driver.find_element(By.CSS_SELECTOR, "[name=\"dq-notify-body-drw\"]").send_keys("CustomDQBodyDRW")
    self.driver.find_element(By.CSS_SELECTOR, "#external-modules-configure-modal .modal-footer .save").click()
    time.sleep(2)
    self.driver.find_element(By.LINK_TEXT, "My Projects").click()
    self.driver.find_element(By.LINK_TEXT, "REDCap UI Tweaker Test").click()
    self.driver.find_element(By.CSS_SELECTOR, "a[href*=\"DataQuality/index.php\"]").click()
    self.driver.execute_script("$('#input_rulename_id_0').val('Example data quality rule')")
    self.driver.execute_script("$('#input_rulelogic_id_0').val(\"[first_name]='DQTest'\")")
    None if (element := self.driver.find_element(By.ID, "rulerte_id_0")).is_selected() else element.click()
    self.driver.find_element(By.CSS_SELECTOR, "button[onclick*=\"addNewRule\"]").click()
    time.sleep(0.5)
    self.driver.find_element(By.CSS_SELECTOR, "a[href*=\"ProjectSetup/index.php\"]").click()
    self.driver.find_element(By.CSS_SELECTOR, "button[onclick*=\"displayCustomizeProjPopup\"]").click()
    None if (element := self.driver.find_element(By.ID, "data_resolution_enabled_chkbx")).is_selected() else element.click()
    self.driver.find_element(By.ID, "data_resolution_enabled").find_element(By.CSS_SELECTOR, "*[value='1']").click()
    self.driver.execute_script("$('#south').remove()")
    self.driver.find_element(By.XPATH, "//div[@aria-describedby='customize_project']//button[contains(.,'Save')]").click()
    WebDriverWait(self.driver, 30).until(expected_conditions.presence_of_element_located((By.ID, "south")))
    self.driver.find_element(By.CSS_SELECTOR, "a[href*=\"record_status_dashboard.php\"]").click()
    self.driver.find_element(By.CSS_SELECTOR, "button.btn-rcgreen[onclick*=\"DataEntry/record_home.php\"]").click()
    self.driver.find_element(By.CSS_SELECTOR, "[name=\"first_name\"]").click()
    self.driver.find_element(By.CSS_SELECTOR, "[name=\"first_name\"]").send_keys("DQTest")
    self.driver.find_element(By.CSS_SELECTOR, "button[id^=\"submit-\"]").click()
    WebDriverWait(self.driver, 30).until(expected_conditions.presence_of_element_located((By.ID, "dq_rules_violated")))
    assert len(self.driver.find_elements(By.XPATH, "//span[@class='ui-dialog-title'][contains(.,'CustomDQHeader')]")) > 0
    assert len(self.driver.find_elements(By.XPATH, "//div[@id='dq_rules_violated'][contains(.,'CustomDQBodyStandard')]")) > 0
    self.driver.find_element(By.XPATH, "//div[@aria-describedby='dq_rules_violated']//button[contains(.,'Close')]").click()
    self.driver.find_element(By.CSS_SELECTOR, "a[href*=\"ProjectSetup/index.php\"]").click()
    self.driver.find_element(By.CSS_SELECTOR, "button[onclick*=\"displayCustomizeProjPopup\"]").click()
    None if (element := self.driver.find_element(By.ID, "data_resolution_enabled_chkbx")).is_selected() else element.click()
    self.driver.find_element(By.ID, "data_resolution_enabled").find_element(By.CSS_SELECTOR, "*[value='2']").click()
    self.driver.execute_script("$('#south').remove()")
    self.driver.find_element(By.XPATH, "//div[@aria-describedby='customize_project']//button[contains(.,'Save')]").click()
    WebDriverWait(self.driver, 30).until(expected_conditions.presence_of_element_located((By.ID, "south")))
    self.driver.find_element(By.XPATH, "//div[@aria-describedby='drw_instruction_popup']//button[contains(.,'Close')]").click()
    self.driver.find_element(By.CSS_SELECTOR, "a[href*=\"record_status_dashboard.php\"]").click()
    self.driver.find_element(By.CSS_SELECTOR, "button.btn-rcgreen[onclick*=\"DataEntry/record_home.php\"]").click()
    self.driver.find_element(By.CSS_SELECTOR, "[name=\"first_name\"]").send_keys("DQTest")
    self.driver.find_element(By.CSS_SELECTOR, "button[id^=\"submit-\"]").click()
    WebDriverWait(self.driver, 30).until(expected_conditions.presence_of_element_located((By.ID, "dq_rules_violated")))
    assert len(self.driver.find_elements(By.XPATH, "//span[@class='ui-dialog-title'][contains(.,'CustomDQHeader')]")) > 0
    assert len(self.driver.find_elements(By.XPATH, "//div[@id='dq_rules_violated'][contains(.,'CustomDQBodyDRW')]")) > 0
    self.driver.find_element(By.XPATH, "//div[@aria-describedby='dq_rules_violated']//button[contains(.,'Close')]").click()
    self.driver.find_element(By.CSS_SELECTOR, "a[href$=\"ControlCenter/index.php\"]").click()
    self.driver.find_element(By.CSS_SELECTOR, "a[href$=\"ExternalModules/manager/control_center.php\"]").click()
    WebDriverWait(self.driver, 30).until(expected_conditions.presence_of_element_located((By.CSS_SELECTOR, "tr[data-module=\"redcap_ui_tweaker\"] button.external-modules-configure-button")))
    self.driver.find_element(By.CSS_SELECTOR, "tr[data-module=\"redcap_ui_tweaker\"] button.external-modules-configure-button").click()
    WebDriverWait(self.driver, 30).until(expected_conditions.presence_of_element_located((By.CSS_SELECTOR, "[name=\"dq-notify-header\"]")))
    self.driver.execute_script("$('[name=\"dq-notify-header\"]').val(JSON.parse(sessionStorage.getItem('test-savedsetting')).header);$('[name=\"dq-notify-body\"]').val(JSON.parse(sessionStorage.getItem('test-savedsetting')).body);$('[name=\"dq-notify-body-drw\"]').val(JSON.parse(sessionStorage.getItem('test-savedsetting')).bodydrw)")
    time.sleep(0.2)
    self.driver.find_element(By.CSS_SELECTOR, "#external-modules-configure-modal .modal-footer .save").click()
    time.sleep(2)
