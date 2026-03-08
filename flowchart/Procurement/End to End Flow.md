```mermaid

flowchart TD
    Start([Low Stock Detected]) --> Alert[Stock Alert Generated]
    Alert --> PR[Create Purchase Requisition]
    
    PR --> PRApproval{PR Approved?}
    PRApproval -->|No| PREnd([PR Rejected])
    PRApproval -->|Yes| CheckRFQ{RFQ Required?}
    
    CheckRFQ -->|No| DirectPO[Create PO Directly]
    CheckRFQ -->|Yes| RFQ[Create RFQ]
    
    RFQ --> SendRFQ[Send to Suppliers]
    SendRFQ --> Quotes[Receive Quotations]
    Quotes --> Evaluate[Evaluate & Compare]
    Evaluate --> Award[Award to Best Supplier]
    Award --> POFromRFQ[Create PO from Quote]
    
    DirectPO --> PO[Purchase Order Created]
    POFromRFQ --> PO
    
    PO --> POApproval{PO Approved?}
    POApproval -->|No| POEnd([PO Rejected])
    POApproval -->|Yes| SendSupplier[Send to Supplier]
    
    SendSupplier --> Delivery[Supplier Delivers]
    Delivery --> GRN[Create Goods Receipt]
    
    GRN --> Inspect[Inspect Goods]
    Inspect --> ReceiptOK{Receipt OK?}
    ReceiptOK -->|Damaged| Damage[Record Damages]
    ReceiptOK -->|Short| Partial[Record Partial]
    ReceiptOK -->|OK| Full[Full Receipt]
    
    Damage --> UpdateInv
    Partial --> UpdateInv
    Full --> UpdateInv[Update Inventory]
    
    UpdateInv --> StockUpdated[Stock Levels Updated]
    StockUpdated --> Payment[Payment Due]
    
    Payment --> PayApproval{Payment Approved?}
    PayApproval -->|No| PayEnd([Payment Rejected])
    PayApproval -->|Yes| ProcessPay[Process Payment]
    
    ProcessPay --> PaySuccess{Success?}
    PaySuccess -->|No| PayFailed([Payment Failed])
    PaySuccess -->|Yes| Complete[Payment Completed]
    
    Complete --> UpdateSupplierRating[Update Supplier Rating]
    UpdateSupplierRating --> End([Procurement Cycle Complete])
    
    style Start fill:#e1f5ff
    style End fill:#e1ffe1
    style PREnd fill:#ffe1e1
    style POEnd fill:#ffe1e1
    style PayEnd fill:#ffe1e1
    style PayFailed fill:#ffe1e1
    style Complete fill:#ccffcc