```mermaid
flowchart TD
    Start([Branch Needs Stock<br/>from Another Branch]) --> CheckInventory[Check Source Branch<br/>Inventory Availability]
    
    CheckInventory --> CreateTransfer[Create Stock Transfer Request]
    CreateTransfer --> SelectItems[Select Products & Quantities]
    SelectItems --> CalculateCost{Calculate Transfer Cost}
    
    CalculateCost --> Method{Cost Method?}
    Method -->|Distance Based| CalcDistance[Get Branch Distance<br/>Cost = Distance × Cost/km]
    Method -->|Fixed Fee| FixedFee[Cost = Fixed Fee]
    Method -->|Value %| ValuePercent[Cost = Goods Value × %]
    Method -->|Manual| ManualEntry[Staff Enters Cost]
    
    CalcDistance --> CostCalculated[Transfer Cost Calculated]
    FixedFee --> CostCalculated
    ValuePercent --> CostCalculated
    ManualEntry --> CostCalculated
    
    CostCalculated --> CheckPolicy{Approval Policy?}
    
    CheckPolicy -->|Sender Only| SenderApproval[Sender Branch Manager<br/>Reviews]
    CheckPolicy -->|Both Branches| BothApproval[Both Branches Must Approve]
    CheckPolicy -->|Finance Required| FinanceApproval[Finance Department<br/>Must Approve]
    CheckPolicy -->|Auto Approve| AutoApprove[Status: APPROVED]
    
    SenderApproval --> SenderDecision{Approve?}
    SenderDecision -->|No| Rejected[Status: REJECTED]
    SenderDecision -->|Yes| CheckStock[Check Stock Availability]
    
    BothApproval --> SenderApproves[Sender Approves]
    SenderApproves --> ReceiverAck[Receiver Acknowledges]
    ReceiverAck --> CheckStock
    
    FinanceApproval --> FinanceReview{Finance Approves?}
    FinanceReview -->|No| Rejected
    FinanceReview -->|Yes| CheckStock
    
    CheckStock --> StockAvailable{Stock Available?}
    StockAvailable -->|No| InsufficientStock[Error: Insufficient Stock]
    InsufficientStock --> Rejected
    
    StockAvailable -->|Yes| Ship[Status: IN_TRANSIT<br/>Warehouse Staff Ships Items]
    Ship --> DeductSource[Deduct from Source Branch<br/>quantity_on_hand -= qty<br/>quantity_available -= qty]
    DeductSource --> CreateTxOut[Create Transaction<br/>Type: transfer_out]
    
    CreateTxOut --> InTransit[Items in Transit<br/>Track with Tracking Number]
    InTransit --> Receive[Destination Branch<br/>Receives Items]
    
    Receive --> RecordReceived[Record Received Quantities<br/>& Damaged Quantities]
    RecordReceived --> AddDestination[Add to Destination Branch<br/>quantity_on_hand += received_qty<br/>quantity_damaged += damaged_qty]
    
    AddDestination --> CreateTxIn[Create Transaction<br/>Type: transfer_in]
    CreateTxIn --> UpdateStatus[Update Stock Status<br/>Both Branches]
    
    UpdateStatus --> Complete[Status: RECEIVED]
    Complete --> End([Transfer Complete])
    
    Rejected --> EndRejected([Transfer Cancelled])
    
    style Start fill:#e1f5ff
    style End fill:#e1ffe1
    style EndRejected fill:#ffe1e1
    style Complete fill:#ccffcc
    style Rejected fill:#ffcccc
    