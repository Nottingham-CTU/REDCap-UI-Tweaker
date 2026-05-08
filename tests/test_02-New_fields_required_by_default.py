# Generated from Selenium IDE
# Test name: t02 - New fields required by default
import pytest
import time
import json
from selenium import webdriver
from selenium.webdriver.common.action_chains import ActionChains
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.support import expected_conditions
from selenium.webdriver.support.wait import WebDriverWait

class Test_02_New_fields_required_by_default:
  def setup_method(self, method):
    self.driver = self.selectedBrowser
    self.vars = {}
  def teardown_method(self, method):
    self.driver.quit()

  def test_02_New_fields_required_by_default(self):
    self.driver.get("http://127.0.0.1/")
    self.driver.find_element(By.LINK_TEXT, "My Projects").click()
    assert len(self.driver.find_elements(By.XPATH, "//*[@id=\"table-proj_table\"][contains(.,'REDCap UI Tweaker Test')]")) > 0
    self.driver.find_element(By.CSS_SELECTOR, "a[href$=\"ControlCenter/index.php\"]").click()
    self.driver.find_element(By.CSS_SELECTOR, "a[href$=\"ExternalModules/manager/control_center.php\"]").click()
    WebDriverWait(self.driver, 30).until(expected_conditions.presence_of_element_located((By.CSS_SELECTOR, "tr[data-module=\"redcap_ui_tweaker\"] button.external-modules-configure-button")))
    self.driver.find_element(By.CSS_SELECTOR, "tr[data-module=\"redcap_ui_tweaker\"] button.external-modules-configure-button").click()
    WebDriverWait(self.driver, 30).until(expected_conditions.presence_of_element_located((By.CSS_SELECTOR, "[name=\"field-default-required\"]")))
    self.driver.execute_script("sessionStorage.setItem('test-savedsetting',$('[name=\"field-default-required\"]:checked').val())")
    self.driver.find_element(By.CSS_SELECTOR, "[name=\"field-default-required\"][value=\"1\"]").click()
    self.driver.find_element(By.CSS_SELECTOR, "#external-modules-configure-modal .modal-footer .save").click()
    time.sleep(2)
    self.driver.find_element(By.LINK_TEXT, "My Projects").click()
    self.driver.find_element(By.LINK_TEXT, "REDCap UI Tweaker Test").click()
    self.driver.find_element(By.CSS_SELECTOR, "a[href*=\"online_designer.php\"]").click()
    self.driver.find_element(By.LINK_TEXT, "Basic Demography Form").click()
    self.driver.find_element(By.ID, "btn-first_name-sh-f").click()
    self.driver.find_element(By.ID, "field_type").find_element(By.CSS_SELECTOR, "*[value='text']").click()
    time.sleep(0.5)
    self.driver.execute_script("if($('[name=\"field_req2\"][onclick*=\"value=\\'1\\'\"]:checked').length == 1 && $('[name=\"field_req\"]').val() == '1') document.body.setAttribute('data-isreq',1)")
    assert len(self.driver.find_elements(By.CSS_SELECTOR, "body[data-isreq]")) > 0
    self.driver.find_element(By.ID, "field_type").find_element(By.CSS_SELECTOR, "*[value='calc']").click()
    time.sleep(0.5)
    self.driver.execute_script("if($('[name=\"field_req2\"][onclick*=\"value=\\'0\\'\"]:checked').length == 1 && $('[name=\"field_req\"]').val() == '0') document.body.setAttribute('data-isreq2',1)")
    assert len(self.driver.find_elements(By.CSS_SELECTOR, "body[data-isreq2]")) > 0
    self.driver.find_element(By.ID, "field_type").find_element(By.CSS_SELECTOR, "*[value='yesno']").click()
    time.sleep(0.5)
    self.driver.execute_script("if($('[name=\"field_req2\"][onclick*=\"value=\\'1\\'\"]:checked').length == 1 && $('[name=\"field_req\"]').val() == '1') document.body.setAttribute('data-isreq3',1)")
    assert len(self.driver.find_elements(By.CSS_SELECTOR, "body[data-isreq3]")) > 0
    self.driver.find_element(By.ID, "field_type").find_element(By.CSS_SELECTOR, "*[value='text']").click()
    self.driver.execute_script("$('#field_annotation').val('@CALCTEXT')")
    time.sleep(0.5)
    self.driver.execute_script("if($('[name=\"field_req2\"][onclick*=\"value=\\'0\\'\"]:checked').length == 1 && $('[name=\"field_req\"]').val() == '0') document.body.setAttribute('data-isreq4',1)")
    assert len(self.driver.find_elements(By.CSS_SELECTOR, "body[data-isreq4]")) > 0
    self.driver.execute_script("$('#field_annotation').val('')")
    time.sleep(0.5)
    self.driver.execute_script("if($('[name=\"field_req2\"][onclick*=\"value=\\'1\\'\"]:checked').length == 1 && $('[name=\"field_req\"]').val() == '1') document.body.setAttribute('data-isreq5',1)")
    assert len(self.driver.find_elements(By.CSS_SELECTOR, "body[data-isreq5]")) > 0
    self.driver.find_element(By.CSS_SELECTOR, ".ui-dialog-titlebar-close").click()
    self.driver.find_element(By.CSS_SELECTOR, "a[href$=\"ControlCenter/index.php\"]").click()
    self.driver.find_element(By.CSS_SELECTOR, "a[href$=\"ExternalModules/manager/control_center.php\"]").click()
    WebDriverWait(self.driver, 30).until(expected_conditions.presence_of_element_located((By.CSS_SELECTOR, "tr[data-module=\"redcap_ui_tweaker\"] button.external-modules-configure-button")))
    self.driver.find_element(By.CSS_SELECTOR, "tr[data-module=\"redcap_ui_tweaker\"] button.external-modules-configure-button").click()
    WebDriverWait(self.driver, 30).until(expected_conditions.presence_of_element_located((By.CSS_SELECTOR, "[name=\"field-default-required\"]")))
    self.driver.execute_script("$('[name=\"field-default-required\"][value=\"'+sessionStorage.getItem('test-savedsetting')+'\"]').click()")
    self.driver.find_element(By.CSS_SELECTOR, "#external-modules-configure-modal .modal-footer .save").click()
    time.sleep(2)
