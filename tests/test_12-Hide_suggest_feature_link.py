# Generated from Selenium IDE
# Test name: t12 - Hide suggest feature link
import pytest
import time
import json
from selenium import webdriver
from selenium.webdriver.common.action_chains import ActionChains
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.support import expected_conditions
from selenium.webdriver.support.wait import WebDriverWait

class Test_12_Hide_suggest_feature_link:
  def setup_method(self, method):
    self.driver = self.selectedBrowser
    self.vars = {}
  def teardown_method(self, method):
    self.driver.quit()

  def test_12_Hide_suggest_feature_link(self):
    self.driver.get("http://127.0.0.1/")
    self.driver.find_element(By.LINK_TEXT, "My Projects").click()
    assert len(self.driver.find_elements(By.XPATH, "//*[@id=\"table-proj_table\"][contains(.,'REDCap UI Tweaker Test')]")) > 0
    self.driver.find_element(By.CSS_SELECTOR, "a[href$=\"ControlCenter/index.php\"]").click()
    self.driver.find_element(By.CSS_SELECTOR, "a[href$=\"ExternalModules/manager/control_center.php\"]").click()
    WebDriverWait(self.driver, 30).until(expected_conditions.presence_of_element_located((By.CSS_SELECTOR, "tr[data-module=\"redcap_ui_tweaker\"] button.external-modules-configure-button")))
    self.driver.find_element(By.CSS_SELECTOR, "tr[data-module=\"redcap_ui_tweaker\"] button.external-modules-configure-button").click()
    WebDriverWait(self.driver, 30).until(expected_conditions.presence_of_element_located((By.CSS_SELECTOR, "[name=\"hide-suggest-feature\"]")))
    self.driver.execute_script("sessionStorage.setItem('test-savedsetting',$('[name=\"hide-suggest-feature\"]').prop('checked'))")
    None if (element := self.driver.find_element(By.CSS_SELECTOR, "[name=\"hide-suggest-feature\"]")).is_selected() else element.click()
    self.driver.find_element(By.CSS_SELECTOR, "#external-modules-configure-modal .modal-footer .save").click()
    time.sleep(2)
    self.driver.find_element(By.LINK_TEXT, "My Projects").click()
    self.driver.find_element(By.LINK_TEXT, "REDCap UI Tweaker Test").click()
    self.driver.execute_script("if ($('a:contains(\"Suggest a New Feature\"):visible').length != $('a:contains(\"Suggest a New Feature\")').length) document.body.setAttribute('data-featurelink',1)")
    assert len(self.driver.find_elements(By.CSS_SELECTOR, "body[data-featurelink]")) > 0
    self.driver.find_element(By.CSS_SELECTOR, "a[href$=\"ControlCenter/index.php\"]").click()
    self.driver.find_element(By.CSS_SELECTOR, "a[href$=\"ExternalModules/manager/control_center.php\"]").click()
    WebDriverWait(self.driver, 30).until(expected_conditions.presence_of_element_located((By.CSS_SELECTOR, "tr[data-module=\"redcap_ui_tweaker\"] button.external-modules-configure-button")))
    self.driver.find_element(By.CSS_SELECTOR, "tr[data-module=\"redcap_ui_tweaker\"] button.external-modules-configure-button").click()
    WebDriverWait(self.driver, 30).until(expected_conditions.presence_of_element_located((By.CSS_SELECTOR, "[name=\"hide-suggest-feature\"]")))
    self.driver.execute_script("$('[name=\"hide-suggest-feature\"]').prop('checked',sessionStorage.getItem('test-savedsetting')=='true')")
    self.driver.find_element(By.CSS_SELECTOR, "#external-modules-configure-modal .modal-footer .save").click()
    time.sleep(2)
