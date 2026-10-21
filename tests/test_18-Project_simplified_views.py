# Generated from Selenium IDE
# Test name: t18 - Project simplified views
import pytest
import time
import json
from selenium import webdriver
from selenium.webdriver.common.action_chains import ActionChains
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.support import expected_conditions
from selenium.webdriver.support.wait import WebDriverWait

class Test_18_Project_simplified_views:
  def setup_method(self, method):
    self.driver = self.selectedBrowser
    self.vars = {}
  def teardown_method(self, method):
    self.driver.quit()

  def test_18_Project_simplified_views(self):
    self.driver.get("http://127.0.0.1/")
    self.driver.find_element(By.LINK_TEXT, "My Projects").click()
    assert len(self.driver.find_elements(By.XPATH, "//*[@id=\"table-proj_table\"][contains(.,'REDCap UI Tweaker Test')]")) > 0
    self.driver.find_element(By.CSS_SELECTOR, "a[href$=\"ControlCenter/index.php\"]").click()
    self.driver.find_element(By.CSS_SELECTOR, "a[href$=\"ExternalModules/manager/control_center.php\"]").click()
    WebDriverWait(self.driver, 30).until(expected_conditions.presence_of_element_located((By.CSS_SELECTOR, "tr[data-module=\"redcap_ui_tweaker\"] button.external-modules-configure-button")))
    self.driver.find_element(By.CSS_SELECTOR, "tr[data-module=\"redcap_ui_tweaker\"] button.external-modules-configure-button").click()
    WebDriverWait(self.driver, 30).until(expected_conditions.presence_of_element_located((By.CSS_SELECTOR, "[name=\"alerts-simplified-view\"]")))
    self.driver.execute_script("sessionStorage.setItem('test-savedsetting',JSON.stringify([$('[name=\"alerts-simplified-view\"]').prop('checked'),$('[name=\"codebook-simplified-view\"]').prop('checked'),$('[name=\"extmod-simplified-view\"]:checked').val(),$('[name=\"quality-rules-simplified-view\"]').prop('checked'),$('[name=\"instrument-simplified-view\"]').prop('checked'),$('[name=\"reports-simplified-view\"]').prop('checked'),$('[name=\"user-rights-simplified-view\"]').prop('checked')]))")
    None if (element := self.driver.find_element(By.NAME, "alerts-simplified-view")).is_selected() else element.click()
    None if (element := self.driver.find_element(By.NAME, "codebook-simplified-view")).is_selected() else element.click()
    None if (element := self.driver.find_element(By.CSS_SELECTOR, "[name=\"extmod-simplified-view\"][value=\"A\"]")).is_selected() else element.click()
    None if (element := self.driver.find_element(By.NAME, "quality-rules-simplified-view")).is_selected() else element.click()
    None if (element := self.driver.find_element(By.NAME, "instrument-simplified-view")).is_selected() else element.click()
    None if (element := self.driver.find_element(By.NAME, "reports-simplified-view")).is_selected() else element.click()
    None if (element := self.driver.find_element(By.NAME, "user-rights-simplified-view")).is_selected() else element.click()
    self.driver.find_element(By.CSS_SELECTOR, "#external-modules-configure-modal .modal-footer .save").click()
    time.sleep(2)
    self.driver.find_element(By.LINK_TEXT, "My Projects").click()
    self.driver.find_element(By.LINK_TEXT, "REDCap UI Tweaker Test").click()
    self.driver.find_element(By.CSS_SELECTOR, "[href*=\"AlertsController:setup\"]").click()
    time.sleep(1)
    assert len(self.driver.find_elements(By.ID, "simplifiedView")) > 0
    self.driver.find_element(By.ID, "simplifiedView").click()
    assert len(self.driver.find_elements(By.ID, "selectTable")) > 0
    self.driver.find_element(By.CSS_SELECTOR, "[href*=\"data_dictionary_codebook.php\"]").click()
    time.sleep(1)
    assert len(self.driver.find_elements(By.ID, "simplifiedView")) > 0
    self.driver.find_element(By.ID, "simplifiedView").click()
    assert len(self.driver.find_elements(By.ID, "selectTable")) > 0
    self.driver.find_element(By.CSS_SELECTOR, "[href*=\"ExternalModules/manager/project.php\"]").click()
    time.sleep(1)
    assert len(self.driver.find_elements(By.ID, "simplifiedView")) > 0
    self.driver.find_element(By.ID, "simplifiedView").click()
    assert len(self.driver.find_elements(By.ID, "selectTable")) > 0
    self.driver.find_element(By.CSS_SELECTOR, "[href*=\"DataQuality/index.php\"]").click()
    time.sleep(1)
    assert len(self.driver.find_elements(By.ID, "simplifiedView")) > 0
    self.driver.find_element(By.ID, "simplifiedView").click()
    assert len(self.driver.find_elements(By.ID, "selectTable")) > 0
    self.driver.find_element(By.CSS_SELECTOR, "[href*=\"ProjectSetup/index.php\"]").click()
    self.driver.find_element(By.ID, "setupLongiBtn").click()
    time.sleep(3)
    self.driver.find_element(By.XPATH, "//button[contains(.,'Define My Events')]").click()
    self.driver.find_element(By.ID, "descrip").send_keys("Event 2")
    self.driver.find_element(By.ID, "addbutton").click()
    time.sleep(3)
    self.driver.find_element(By.CSS_SELECTOR, "a[href*=\"Design/designate_forms.php\"]").click()
    time.sleep(1)
    assert len(self.driver.find_elements(By.ID, "simplifiedView")) > 0
    self.driver.find_element(By.ID, "simplifiedView").click()
    assert len(self.driver.find_elements(By.ID, "selectTable")) > 0
    self.driver.find_element(By.CSS_SELECTOR, "[href*=\"ProjectSetup/index.php\"]").click()
    self.driver.find_element(By.XPATH, "//button[contains(.,'Define My Events')]").click()
    self.driver.find_element(By.XPATH, "(//a[contains(@onclick, 'delVisit')])[last()]").click()
    self.driver.switch_to.alert.accept()
    time.sleep(3)
    self.driver.find_element(By.CSS_SELECTOR, "[href*=\"ProjectSetup/index.php\"]").click()
    self.driver.find_element(By.ID, "setupLongiBtn").click()
    time.sleep(3)
    self.driver.find_element(By.CSS_SELECTOR, "a[href*=\"DataExport/index.php\"]").click()
    time.sleep(1)
    assert len(self.driver.find_elements(By.ID, "simplifiedView")) > 0
    self.driver.find_element(By.ID, "simplifiedView").click()
    assert len(self.driver.find_elements(By.ID, "selectTable")) > 0
    self.driver.find_element(By.CSS_SELECTOR, "a[href*=\"UserRights/index.php\"]").click()
    time.sleep(1)
    assert len(self.driver.find_elements(By.ID, "simplifiedView")) > 0
    self.driver.find_element(By.ID, "simplifiedView").click()
    assert len(self.driver.find_elements(By.ID, "selectTable")) > 0
    self.driver.find_element(By.CSS_SELECTOR, "a[href$=\"ControlCenter/index.php\"]").click()
    self.driver.find_element(By.CSS_SELECTOR, "a[href$=\"ExternalModules/manager/control_center.php\"]").click()
    WebDriverWait(self.driver, 30).until(expected_conditions.presence_of_element_located((By.CSS_SELECTOR, "tr[data-module=\"redcap_ui_tweaker\"] button.external-modules-configure-button")))
    self.driver.find_element(By.CSS_SELECTOR, "tr[data-module=\"redcap_ui_tweaker\"] button.external-modules-configure-button").click()
    WebDriverWait(self.driver, 30).until(expected_conditions.presence_of_element_located((By.CSS_SELECTOR, "[name=\"alerts-simplified-view\"]")))
    self.driver.execute_script("$('[name=\"alerts-simplified-view\"]').prop('checked',JSON.parse(sessionStorage.getItem('test-savedsetting'))[0]);$('[name=\"codebook-simplified-view\"]').prop('checked',JSON.parse(sessionStorage.getItem('test-savedsetting'))[1]);$('[name=\"extmod-simplified-view\"][value=\"'+JSON.parse(sessionStorage.getItem('test-savedsetting'))[2]+'\"').prop('checked',true);$('[name=\"quality-rules-simplified-view\"]').prop('checked',JSON.parse(sessionStorage.getItem('test-savedsetting'))[3]);$('[name=\"instrument-simplified-view\"]').prop('checked',JSON.parse(sessionStorage.getItem('test-savedsetting'))[4]);$('[name=\"reports-simplified-view\"]').prop('checked',JSON.parse(sessionStorage.getItem('test-savedsetting'))[5]);$('[name=\"user-rights-simplified-view\"]').prop('checked',JSON.parse(sessionStorage.getItem('test-savedsetting'))[6])")
    self.driver.find_element(By.CSS_SELECTOR, "#external-modules-configure-modal .modal-footer .save").click()
    time.sleep(2)
