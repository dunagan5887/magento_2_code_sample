<?php
/**
 * Author: Sean Dunagan (github: dunagan5887)
 * Date: 10/11/16
 */

namespace Dunagan\MagentoFixes\Plugin\Customer\Controller\Account;

use Magento\Customer\Model\Session;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Framework\Exception\State\InvalidTransitionException;

/**
 * Class ConfirmationPlugin
 * @package Dunagan\MagentoFixes\Plugin\Customer\Controller\Account
 */
class ConfirmationPlugin extends \Magento\Customer\Controller\Account\Confirmation
{
    /**
     * The Confirmation::execute() method has an issue where it attempts to get the 'email' param as a post parameter,
     *  although the request can be a GET method from a click-able link on the frontend of the website.
     * As such, we need to replicate the functionality while changing that one line. As such, we will not call the
     *  $proceed callable
     *
     * @param \Magento\Customer\Controller\Account\Confirmation\Interceptor $interceptor
     * @param callable $proceed
     *
     * @return \Magento\Framework\Controller\Result\Redirect|\Magento\Framework\View\Result\Page
     */
    public function aroundExecute($interceptor, $proceed)
    {
        if ($this->session->isLoggedIn()) {
            /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('*/*/');
            return $resultRedirect;
        }

        // try to confirm by email
        // BEGIN CUSTOM Dunagan CODE
        $email = $this->getRequest()->getParam('email'); // getParam() instead of getPost()
        // END CUSTOMER Dunagan CODE
        if ($email) {
            /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
            $resultRedirect = $this->resultRedirectFactory->create();

            try {
                $this->customerAccountManagement->resendConfirmation(
                    $email,
                    $this->storeManager->getStore()->getWebsiteId()
                );
                $this->messageManager->addSuccess(__('Please check your email for confirmation key.'));
            } catch (InvalidTransitionException $e) {
                $this->messageManager->addSuccess(__('This email does not require confirmation.'));
            } catch (\Exception $e) {
                $this->messageManager->addException($e, __('Wrong email.'));
                $resultRedirect->setPath('*/*/*', ['email' => $email, '_secure' => true]);
                return $resultRedirect;
            }
            $this->session->setUsername($email);
            $resultRedirect->setPath('*/*/index', ['_secure' => true]);
            return $resultRedirect;
        }

        /** @var \Magento\Framework\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getLayout()->getBlock('accountConfirmation')->setEmail(
            $this->getRequest()->getParam('email', $email)
        );
        return $resultPage;
    }
}
