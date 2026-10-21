# Generated from Selenium IDE
# Test name: t16 - Single project redirection
import pytest
import time
import json
from selenium import webdriver
from selenium.webdriver.common.action_chains import ActionChains
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.support import expected_conditions
from selenium.webdriver.support.wait import WebDriverWait

class Test_16_Single_project_redirection:
  def setup_method(self, method):
    self.driver = self.selectedBrowser
    self.vars = {}
  def teardown_method(self, method):
    self.driver.quit()

  def test_16_Single_project_redirection(self):
    self.driver.get("http://127.0.0.1/")
    self.driver.find_element(By.LINK_TEXT, "My Projects").click()
    assert len(self.driver.find_elements(By.XPATH, "//*[@id=\"table-proj_table\"][contains(.,'REDCap UI Tweaker Test')]")) > 0
    self.driver.find_element(By.CSS_SELECTOR, "a[href$=\"ControlCenter/index.php\"]").click()
    self.driver.find_element(By.CSS_SELECTOR, "a[href$=\"ExternalModules/manager/control_center.php\"]").click()
    WebDriverWait(self.driver, 30).until(expected_conditions.presence_of_element_located((By.CSS_SELECTOR, "tr[data-module=\"redcap_ui_tweaker\"] button.external-modules-configure-button")))
    self.driver.find_element(By.CSS_SELECTOR, "tr[data-module=\"redcap_ui_tweaker\"] button.external-modules-configure-button").click()
    WebDriverWait(self.driver, 30).until(expected_conditions.presence_of_element_located((By.CSS_SELECTOR, "[name=\"single-project-redirect\"]")))
    self.driver.execute_script("sessionStorage.setItem('test-savedsetting',$('[name=\"single-project-redirect\"]').prop('checked'))")
    None if (element := self.driver.find_element(By.CSS_SELECTOR, "[name=\"single-project-redirect\"]")).is_selected() else element.click()
    self.driver.find_element(By.CSS_SELECTOR, "#external-modules-configure-modal .modal-footer .save").click()
    time.sleep(2)
    self.driver.find_element(By.LINK_TEXT, "My Projects").click()
    self.driver.execute_script("$('#footer').remove()")
    self.driver.find_element(By.LINK_TEXT, "Log out").click()
    WebDriverWait(self.driver, 30).until(expected_conditions.presence_of_element_located((By.ID, "footer")))
    self.driver.execute_script("$('#username').val(JSON.parse(sessionStorage.getItem('test-userdetails')).user2);$('#password').val(JSON.parse(sessionStorage.getItem('test-userdetails')).pass2)")
    self.driver.find_element(By.ID, "login_btn").click()
    WebDriverWait(self.driver, 30).until(expected_conditions.presence_of_element_located((By.CSS_SELECTOR, "a[href*=\"index.php?action=myprojects\"]")))
    self.driver.execute_script("$(function(){if (window.location.search.includes('pid=')) document.body.setAttribute('data-oneproj',1)})")
    assert len(self.driver.find_elements(By.CSS_SELECTOR, "body[data-oneproj]")) > 0
    self.driver.find_element(By.LINK_TEXT, "Log out").click()
    time.sleep(1)
    self.driver.get("http://127.0.0.1/")
    self.driver.execute_script("$('#username').val(JSON.parse(sessionStorage.getItem('test-userdetails')).user1);$('#password').val(JSON.parse(sessionStorage.getItem('test-userdetails')).pass1)")
    self.driver.find_element(By.ID, "login_btn").click()
    self.driver.find_element(By.CSS_SELECTOR, "a[href$=\"ControlCenter/index.php\"]").click()
    self.driver.find_element(By.CSS_SELECTOR, "a[href$=\"ExternalModules/manager/control_center.php\"]").click()
    WebDriverWait(self.driver, 30).until(expected_conditions.presence_of_element_located((By.CSS_SELECTOR, "tr[data-module=\"redcap_ui_tweaker\"] button.external-modules-configure-button")))
    self.driver.find_element(By.CSS_SELECTOR, "tr[data-module=\"redcap_ui_tweaker\"] button.external-modules-configure-button").click()
    WebDriverWait(self.driver, 30).until(expected_conditions.presence_of_element_located((By.CSS_SELECTOR, "[name=\"single-project-redirect\"]")))
    self.driver.execute_script("$('[name=\"single-project-redirect\"]').prop('checked',sessionStorage.getItem('test-savedsetting')=='true')")
    self.driver.find_element(By.CSS_SELECTOR, "#external-modules-configure-modal .modal-footer .save").click()
    time.sleep(2)
