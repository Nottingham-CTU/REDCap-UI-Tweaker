# Generated from Selenium IDE
# Test name: t03 - Predefined field annotations
import pytest
import time
import json
from selenium import webdriver
from selenium.webdriver.common.action_chains import ActionChains
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.support import expected_conditions
from selenium.webdriver.support.wait import WebDriverWait

class Test_03_Predefined_field_annotations:
  def setup_method(self, method):
    self.driver = self.selectedBrowser
    self.vars = {}
  def teardown_method(self, method):
    self.driver.quit()

  def test_03_Predefined_field_annotations(self):
    self.driver.get("http://127.0.0.1/")
    self.driver.find_element(By.LINK_TEXT, "My Projects").click()
    assert len(self.driver.find_elements(By.XPATH, "//*[@id=\"table-proj_table\"][contains(.,'REDCap UI Tweaker Test')]")) > 0
    self.driver.find_element(By.CSS_SELECTOR, "a[href$=\"ControlCenter/index.php\"]").click()
    self.driver.find_element(By.CSS_SELECTOR, "a[href$=\"ExternalModules/manager/control_center.php\"]").click()
    WebDriverWait(self.driver, 30).until(expected_conditions.presence_of_element_located((By.CSS_SELECTOR, "tr[data-module=\"redcap_ui_tweaker\"] button.external-modules-configure-button")))
    self.driver.find_element(By.CSS_SELECTOR, "tr[data-module=\"redcap_ui_tweaker\"] button.external-modules-configure-button").click()
    WebDriverWait(self.driver, 30).until(expected_conditions.presence_of_element_located((By.CSS_SELECTOR, "[name=\"predefined-annotations\"]")))
    self.driver.execute_script("sessionStorage.setItem('test-savedsetting',$('[name=\"predefined-annotations\"]').val())")
    self.driver.execute_script("$('[name=\"predefined-annotations\"]').val(decodeURIComponent('Annotation1%0aAnnotation2%0aAnnotation3'))")
    self.driver.find_element(By.CSS_SELECTOR, "#external-modules-configure-modal .modal-footer .save").click()
    time.sleep(2)
    self.driver.find_element(By.LINK_TEXT, "My Projects").click()
    self.driver.find_element(By.LINK_TEXT, "REDCap UI Tweaker Test").click()
    self.driver.find_element(By.CSS_SELECTOR, "a[href*=\"online_designer.php\"]").click()
    self.driver.find_element(By.LINK_TEXT, "Basic Demography Form").click()
    self.driver.find_element(By.ID, "btn-first_name-sh-f").click()
    self.driver.find_element(By.ID, "field_type").find_element(By.CSS_SELECTOR, "*[value='text']").click()
    time.sleep(0.5)
    assert len(self.driver.find_elements(By.CSS_SELECTOR, "#div_parent_field_annotation select[style*=\"x-small\"]")) > 0
    self.driver.find_element(By.CSS_SELECTOR, "#div_parent_field_annotation select[style*=\"x-small\"]").find_element(By.XPATH, "(descendant::option)[. = 'Annotation1']").click()
    time.sleep(0.2)
    self.driver.execute_script("if($('#field_annotation').val() == 'Annotation1') document.body.setAttribute('data-annotated',1)")
    assert len(self.driver.find_elements(By.CSS_SELECTOR, "body[data-annotated]")) > 0
    self.driver.find_element(By.CSS_SELECTOR, ".ui-dialog-titlebar-close").click()
    self.driver.find_element(By.CSS_SELECTOR, "a[href$=\"ControlCenter/index.php\"]").click()
    self.driver.find_element(By.CSS_SELECTOR, "a[href$=\"ExternalModules/manager/control_center.php\"]").click()
    WebDriverWait(self.driver, 30).until(expected_conditions.presence_of_element_located((By.CSS_SELECTOR, "tr[data-module=\"redcap_ui_tweaker\"] button.external-modules-configure-button")))
    self.driver.find_element(By.CSS_SELECTOR, "tr[data-module=\"redcap_ui_tweaker\"] button.external-modules-configure-button").click()
    WebDriverWait(self.driver, 30).until(expected_conditions.presence_of_element_located((By.CSS_SELECTOR, "[name=\"predefined-annotations\"]")))
    self.driver.execute_script("$('[name=\"predefined-annotations\"]').val(sessionStorage.getItem('test-savedsetting'))")
    time.sleep(0.2)
    self.driver.find_element(By.CSS_SELECTOR, "#external-modules-configure-modal .modal-footer .save").click()
    time.sleep(2)
