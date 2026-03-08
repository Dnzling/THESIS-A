flowchart TD
    Start([Create Purchase Order]) --> PODetails[Enter PO Details:<br/>- Supplier<br/>- Items & Quantities<br/>- Unit Costs<br/>- Payment Terms]
    
    PODetails --> CalcTotal[Calculate:<br/>Subtotal<br/>+ Tax 12%<br/>+ Shipping<br/>- Discount<br/>= Total Amount]
    
    CalcTotal --> GetTier[Get Approval Tier<br/>Based on Total Amount]
    GetTier --> RequiredApprovers[Set Required Approvers<br/>From Approval Tiers]
    
    RequiredApprovers --> Status1[Status: DRAFT]
    Status1 --> SubmitPO[Submit for Approval<br/>Status: PENDING_APPROVAL]
    
    SubmitPO --> ApprovalChain[Approval Chain Starts]
    ApprovalChain --> FirstApprover[First Approver Reviews]
    
    FirstApprover --> Approve1{Approve?}
    Approve1 -->|No| Rejected[Status: REJECTED<br/>Record Reason]
    Approve1 -->|Yes| AddApproval1[Add to approvals_received]
    
    AddApproval1 --> CheckComplete{All Approvers Done?}
    CheckComplete -->|No| Status2[Status: PARTIALLY_APPROVED]
    Status2 --> NextApprover[Next Approver Reviews]
    NextApprover --> Approve1
    
    CheckComplete -->|Yes| FullyApproved[Status: FULLY_APPROVED]
    FullyApproved --> FinanceReview[Finance Reviews Payment]
    
    FinanceReview --> FinanceDecision{Finance Approves?}
    FinanceDecision -->|No| Rejected
    FinanceDecision -->|Yes| FinanceApproved[Status: FINANCE_APPROVED]
    
    FinanceApproved --> SendSupplier[Send PO to Supplier<br/>Status: ORDERED]
    SendSupplier --> UpdateSupplier[Update Supplier Stats:<br/>total_orders++<br/>total_amount_purchased += amount]
    
    UpdateSupplier --> WaitDelivery[Wait for Delivery<br/>Track Expected Date]
    WaitDelivery --> SupplierShips[Supplier Ships Items]
    
    SupplierShips --> BranchReceives[Branch Warehouse<br/>Receives Delivery]
    BranchReceives --> CreateGRN[Create Goods Receipt Note GRN]
    
    CreateGRN --> InspectGoods[Inspect Goods:<br/>- Count Quantities<br/>- Check Quality<br/>- Note Damages]
    
    InspectGoods --> RecordReceipt[Record for Each Item:<br/>- Quantity Expected<br/>- Quantity Received<br/>- Quantity Damaged<br/>- Condition]
    
    RecordReceipt --> ReceiptStatus{Receipt Status?}
    ReceiptStatus -->|All Received| FullReceipt[Receipt Status: FULL]
    ReceiptStatus -->|Some Missing| PartialReceipt[Receipt Status: PARTIAL]
    ReceiptStatus -->|Damages| DamagedReceipt[Receipt Status: DAMAGED]
    ReceiptStatus -->|Reject All| RejectedReceipt[Receipt Status: REJECTED]
    
    FullReceipt --> UpdateInventory
    PartialReceipt --> UpdateInventory
    DamagedReceipt --> UpdateInventory
    RejectedReceipt --> ReturnSupplier[Return to Supplier]
    
    UpdateInventory[Update Branch Inventory] --> AddStock[For Each Item:<br/>quantity_on_hand += received_qty<br/>quantity_available += received_qty<br/>quantity_damaged += damaged_qty]
    
    AddStock --> UpdateCost[Update Costs:<br/>Calculate new average_cost<br/>= old_cost × old_qty + new_cost × new_qty / total_qty]
    
    UpdateCost --> CreateTx[Create Inventory Transaction<br/>Type: purchase<br/>Reference: GRN]
    
    CreateTx --> DamageCheck{Any Damages?}
    DamageCheck -->|Yes| CreateDamageTx[Create Transaction<br/>Type: damage]
    DamageCheck -->|No| UpdatePOStatus
    
    CreateDamageTx --> UpdatePOStatus{All Items Received?}
    UpdatePOStatus -->|Yes| POReceived[PO Status: RECEIVED<br/>Record actual_delivery_date]
    UpdatePOStatus -->|No| POPartial[PO Status: PARTIALLY_RECEIVED]
    
    POReceived --> CheckDelivery{On Time?}
    POPartial --> CheckDelivery
    
    CheckDelivery -->|Yes| OnTime[Update Supplier:<br/>on_time_deliveries++]
    CheckDelivery -->|No| Late[Update Supplier:<br/>late_deliveries++]
    
    OnTime --> UpdateRating[Update Supplier Rating]
    Late --> UpdateRating
    
    UpdateRating --> UpdateStockStatus[Update stock_status<br/>in_stock/low_stock/out_of_stock]
    UpdateStockStatus --> CheckAlerts{Triggers Alert?}
    
    CheckAlerts -->|No Alert| Complete[Process Complete]
    CheckAlerts -->|Low Stock| CreateAlert1[Create Low Stock Alert]
    CheckAlerts -->|Generate RO| CreateAlert2[Create Reorder Alert]
    
    CreateAlert1 --> Complete
    CreateAlert2 --> Complete
    
    Complete --> PaymentDue[Payment Becomes Due<br/>Based on Payment Terms]
    
    Rejected --> EndRejected([PO Rejected])
    ReturnSupplier --> EndReturn([Items Returned])
    PaymentDue --> End([Process Complete])
    
    style Start fill:#e1f5ff
    style End fill:#e1ffe1
    style EndRejected fill:#ffe1e1
    style EndReturn fill:#ffe1e1
    style Complete fill:#ccffcc
    style Rejected fill:#ffcccc