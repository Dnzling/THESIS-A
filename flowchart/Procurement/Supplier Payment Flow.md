```mermaid

flowchart TD
    Start([Payment Due<br/>for Purchase Order]) --> CheckDue{Payment Due?}
    CheckDue -->|Not Due Yet| Wait[Wait Until Due Date]
    CheckDue -->|Due/Overdue| CreatePayment[Create Payment Request]
    
    CreatePayment --> PaymentDetails[Enter Details:<br/>- Payment Amount<br/>- Payment Method<br/>- Bank Details<br/>- Reference Number]
    
    PaymentDetails --> Status1[Status: PENDING_APPROVAL]
    Status1 --> NotifyFinance[Notify Finance Department]
    
    NotifyFinance --> FinanceReview[Finance Manager Reviews]
    FinanceReview --> CheckPO[Verify:<br/>- PO Fully Received?<br/>- Amount Correct?<br/>- Supplier Details Valid?]
    
    CheckPO --> FinanceDecision{Approve Payment?}
    FinanceDecision -->|No| Rejected[Status: CANCELLED<br/>Record Reason]
    FinanceDecision -->|Yes| FinanceApproves[Status: APPROVED<br/>Finance Approves]
    
    FinanceApproves --> ProcessPayment[Status: PROCESSING<br/>Initiate Payment]
    
    ProcessPayment --> PaymentMethod{Payment Method?}
    PaymentMethod -->|Cash| CashPayment[Prepare Cash]
    PaymentMethod -->|Check| CheckPayment[Prepare Check<br/>Record Check Number]
    PaymentMethod -->|Bank Transfer| BankTransfer[Initiate Bank Transfer<br/>Record Transaction ID]
    PaymentMethod -->|Online| OnlinePayment[Process Online Payment]
    
    CashPayment --> ExecutePayment[Execute Payment]
    CheckPayment --> ExecutePayment
    BankTransfer --> ExecutePayment
    OnlinePayment --> ExecutePayment
    
    ExecutePayment --> PaymentResult{Payment Success?}
    PaymentResult -->|Failed| Failed[Status: FAILED<br/>Record Error]
    PaymentResult -->|Success| Success[Status: COMPLETED]
    
    Success --> UpdatePO[Update PO:<br/>payment_status = 'paid']
    UpdatePO --> UpdateSupplier[Update Supplier:<br/>current_balance -= payment_amount]
    
    UpdateSupplier --> RecordPayment[Record:<br/>- processed_by<br/>- processed_at<br/>- Payment Date]
    
    RecordPayment --> NotifySupplier[Notify Supplier<br/>Payment Completed]
    NotifySupplier --> GenerateReceipt[Generate Payment Receipt]
    
    Failed --> RetryDecision{Retry?}
    RetryDecision -->|Yes| ProcessPayment
    RetryDecision -->|No| Cancelled[Status: CANCELLED]
    
    GenerateReceipt --> End([Payment Complete])
    Rejected --> EndRejected([Payment Rejected])
    Cancelled --> EndCancelled([Payment Cancelled])
    
    style Start fill:#e1f5ff
    style End fill:#e1ffe1
    style EndRejected fill:#ffe1e1
    style EndCancelled fill:#ffe1e1
    style Success fill:#ccffcc
    style Rejected fill:#ffcccc
    style Failed fill:#ffcccc
    