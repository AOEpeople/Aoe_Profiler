<?php
/**
 * Admihtml Profiler Controller
 *
 * @author Fabrizio Branca
 * @since 2014-08-15
 */
class Aoe_Profiler_Adminhtml_ProfilerController extends Mage_Adminhtml_Controller_Action
{

    /**
     * Load layout, set active menu and breadcrumbs
     *
     * @return $this
     */
    protected function _initAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('system/aoe_profiler')
            ->_addBreadcrumb(Mage::helper('aoe_profiler')->__('System'),
                Mage::helper('aoe_profiler')->__('System'))
            ->_addBreadcrumb(Mage::helper('aoe_profiler')->__('AOE Profiler'),
                Mage::helper('aoe_profiler')->__('AOE Profiler'));
        return $this;
    }

    /**
     * Init stack instance object and set it to registry
     *
     * @return Aoe_Profiler_Model_Run|false
     */
    protected function _initStackInstance()
    {
        $this->_title($this->__('System'))->_title($this->__('AOE Profiler'));

        $stackId = $this->getRequest()->getParam('stack_id', null);

        if ($stackId) {
            $stackInstance = Mage::getModel('aoe_profiler/run'); /* @var $stackInstance Aoe_Profiler_Model_Run */
            $stackInstance->load($stackId);
            if (!$stackInstance->getId()) {
                $this->_getSession()->addError(Mage::helper('aoe_profiler')->__('No data found with this id.'));
                return false;
            }
            Mage::register('current_stack_instance', $stackInstance);
            return $stackInstance;
        }
        return false;
    }

    /**
     * Layout Grid
     */
    public function indexAction()
    {
        $this->_title($this->__('System'))->_title($this->__('AOE Profiler'));

        $this->_initAction();
        $this->renderLayout();
    }

    /**
     * Layout Grid
     */
    public function gridAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }

    /**
     * Edit layout instance action
     *
     */
    public function viewAction()
    {
        $stack = $this->_initStackInstance();
        if (!$stack) {
            $this->_redirect('*/*/');
            return;
        }
        $this->_initAction();
        $this->renderLayout();
    }

    /**
     * Edit layout instance action
     *
     */
    public function deleteAction()
    {
        if ($stack = $this->_initStackInstance()) {
            try {
                // init model and delete
                $stack->delete();
                // display success message
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('aoe_profiler')->__('The profile has been deleted.')
                );
                // go to grid
                $this->_redirect('*/*/');
                return;
            } catch (Mage_Core_Exception $e) {
                $this->_getSession()->addError($e->getMessage());
            } catch (Exception $e) {
                $this->_getSession()->addError(
                    Mage::helper('aoe_profiler')->__('An error occurred while deleting profile data. Please review log and try again.')
                );
                Mage::logException($e);
                // redirect to edit form
                $this->_redirect('*/*/view', array('_current' => true));
                return;
            }
        }
        // display error message
        Mage::getSingleton('adminhtml/session')->addError(
            Mage::helper('aoe_profiler')->__('Unable to find a profile to delete.')
        );
        // go to grid
        $this->_redirect('*/*/');
    }

    /**
     * Delete specified banners using grid massaction
     *
     */
    public function massDeleteAction()
    {
        $ids = $this->getRequest()->getParam('profile');
        if (!is_array($ids)) {
            $this->_getSession()->addError($this->__('Please select profile(s).'));
        } else {
            try {
                foreach ($ids as $id) {
                    $stack = $this->_initStackInstance();
                    $stack->delete();
                }

                $this->_getSession()->addSuccess(
                    $this->__('Total of %d record(s) have been deleted.', count($ids))
                );
            } catch (Mage_Core_Exception $e) {
                $this->_getSession()->addError($e->getMessage());
            } catch (Exception $e) {
                $this->_getSession()->addError(
                    Mage::helper('aoe_profiler')->__('An error occurred while mass deleting profiles. Please review log and try again.')
                );
                Mage::logException($e);
                return;
            }
        }
        $this->_redirect('*/*/index');
    }

    /***
     * Render Profile to a file
     */
    public function renderAction()
    {
        try{
            $stack = $this->_initStackInstance();
            $filename = Mage::helper('aoe_profiler')->renderProfilerOutputToFile();
            $this->_getSession()->addSuccess(
                $this->__('Render successful. Please go to <a href="%s">this page.</a>', Mage::getStoreConfig(Mage_Core_Model_Url::XML_PATH_SECURE_URL).'var/profile/' . $filename)
            );
        } catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        } catch (Exception $e) {
            $this->_getSession()->addError(
                Mage::helper('aoe_profiler')->__('An error occurred while mass deleting profiles. Please review log and try again.')
            );
            Mage::logException($e);
            return;
        }

        // go to grid
        $this->_redirect('*/*/');

    }


    /**
     * Check is allowed access to action
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('system/aoe_profiler');
    }

}