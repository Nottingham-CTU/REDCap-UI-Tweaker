# Generated from Selenium IDE
# Test name: t15 - My projects alphabetical
import pytest
import time
import json
from selenium import webdriver
from selenium.webdriver.common.action_chains import ActionChains
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.support import expected_conditions
from selenium.webdriver.support.wait import WebDriverWait

class Test_15_My_projects_alphabetical:
  def setup_method(self, method):
    self.driver = self.selectedBrowser
    self.vars = {}
  def teardown_method(self, method):
    self.driver.quit()

  def test_15_My_projects_alphabetical(self):
    self.driver.get("http://127.0.0.1/")
    self.driver.find_element(By.LINK_TEXT, "My Projects").click()
    assert len(self.driver.find_elements(By.XPATH, "//*[@id=\"table-proj_table\"][contains(.,'REDCap UI Tweaker Test')]")) > 0
    self.driver.find_element(By.CSS_SELECTOR, "a[href$=\"ControlCenter/index.php\"]").click()
    self.driver.find_element(By.CSS_SELECTOR, "a[href$=\"ExternalModules/manager/control_center.php\"]").click()
    WebDriverWait(self.driver, 30).until(expected_conditions.presence_of_element_located((By.CSS_SELECTOR, "tr[data-module=\"redcap_ui_tweaker\"] button.external-modules-configure-button")))
    self.driver.find_element(By.CSS_SELECTOR, "tr[data-module=\"redcap_ui_tweaker\"] button.external-modules-configure-button").click()
    WebDriverWait(self.driver, 30).until(expected_conditions.presence_of_element_located((By.CSS_SELECTOR, "[name=\"my-projects-alphabetical\"]")))
    self.driver.execute_script("sessionStorage.setItem('test-savedsetting',$('[name=\"my-projects-alphabetical\"]').prop('checked'))")
    None if (element := self.driver.find_element(By.CSS_SELECTOR, "[name=\"my-projects-alphabetical\"]")).is_selected() else element.click()
    self.driver.find_element(By.CSS_SELECTOR, "#external-modules-configure-modal .modal-footer .save").click()
    time.sleep(2)
    self.driver.find_element(By.LINK_TEXT, "My Projects").click()
    time.sleep(2)
    self.driver.execute_script("let vLastName = ''; let vLastFolder = ''; let vIsAlpha = true;$('tr[id^=\"f_\"]').each(function(){if($(this).attr('id').replace(/^f_(-?[0-9]+).*/,'$1') == vLastFolder && vLastName > $(this).attr('data-studyname')) vIsAlpha = false; vLastName = $(this).attr('data-studyname'); vLastFolder = $(this).attr('id').replace(/^f_(-?[0-9]+).*/,'$1')});if (vIsAlpha) document.body.setAttribute('data-alphabetical',1)")
    assert len(self.driver.find_elements(By.CSS_SELECTOR, "body[data-alphabetical]")) > 0
    self.driver.find_element(By.CSS_SELECTOR, "a[href$=\"ControlCenter/index.php\"]").click()
    self.driver.find_element(By.CSS_SELECTOR, "a[href$=\"ExternalModules/manager/control_center.php\"]").click()
    WebDriverWait(self.driver, 30).until(expected_conditions.presence_of_element_located((By.CSS_SELECTOR, "tr[data-module=\"redcap_ui_tweaker\"] button.external-modules-configure-button")))
    self.driver.find_element(By.CSS_SELECTOR, "tr[data-module=\"redcap_ui_tweaker\"] button.external-modules-configure-button").click()
    WebDriverWait(self.driver, 30).until(expected_conditions.presence_of_element_located((By.CSS_SELECTOR, "[name=\"my-projects-alphabetical\"]")))
    self.driver.execute_script("$('[name=\"my-projects-alphabetical\"]').prop('checked',sessionStorage.getItem('test-savedsetting')=='true')")
    self.driver.find_element(By.CSS_SELECTOR, "#external-modules-configure-modal .modal-footer .save").click()
    time.sleep(2)
