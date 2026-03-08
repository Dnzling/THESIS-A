```mermaid

flowchart TD
    Start([Staff Notices<br/>Low Stock]) --> CreatePR[Create Purchase Requisition]
    CreatePR --> AddItems[Add Products & Quantities<br/>Estimate Costs]
    AddItems --> CalcAmount[Calculate Estimated Amount]
    
    CalcAmount --> CheckRoute{Procurement Route?}
    
    CheckRoute --> CheckThreshold{Amount >= Threshold?}
    CheckThreshold -->|No| BranchDirect[Route: BRANCH_DIRECT<br/>Branch can handle]
    CheckThreshold -->|Yes| Centralized[Route: CENTRALIZED<br/>Goes to Procurement Dept]
    
    CheckThreshold --> CheckRFQ{RFQ Required?}
    CheckRFQ -->|Yes| RFQRoute[Route: RFQ_REQUIRED]
    
    BranchDirect --> SubmitPR[Submit PR]
    Centralized --> SubmitPR
    RFQRoute --> SubmitPR
    
    SubmitPR --> Status1[Status: SUBMITTED]
    Status1 --> ApprovalLoop[Approval Chain Loop]
    
    ApprovalLoop --> GetTier[Get Approval Tier<br/>Based on Amount]
    GetTier --> Tier{Which Tier?}
    
    Tier -->|Tier 1<br/>₱0-20K| Approver1[Warehouse Manager]
    Tier -->|Tier 2<br/>₱20K-100K| Approver2[Warehouse + Branch Manager]
    Tier -->|Tier 3<br/>₱100K-500K| Approver3[Warehouse + Finance Manager]
    Tier -->|Tier 4<br/>₱500K+| Approver4[Warehouse + Finance + Admin]
    
    Approver1 --> Approve1{Approve?}
    Approver2 --> Approve2{All Approve?}
    Approver3 --> Approve3{All Approve?}
    Approver4 --> Approve4{All Approve?}
    
    Approve1 -->|No| PRRejected[Status: REJECTED]
    Approve2 -->|No| PRRejected
    Approve3 -->|No| PRRejected
    Approve4 -->|No| PRRejected
    
    Approve1 -->|Yes| Approved[Status: WAREHOUSE_APPROVED]
    Approve2 -->|Yes| Approved
    Approve3 -->|Yes| Approved
    Approve4 -->|Yes| Approved
    
    Approved --> NextStep{Next Step?}
    
    NextStep -->|RFQ Required| CreateRFQ[Create RFQ<br/>Invite Suppliers]
    NextStep -->|Direct Purchase| CreatePO[Create Purchase Order]
    
    CreateRFQ --> RFQFlow[Go to RFQ Flow]
    CreatePO --> POFlow[Go to PO Flow]
    
    PRRejected --> EndRejected([PR Rejected])
    RFQFlow --> EndRFQ([Continue to RFQ])
    POFlow --> EndPO([Continue to PO])
    
    style Start fill:#e1f5ff
    style EndRejected fill:#ffe1e1
    style EndRFQ fill:#fff5e1
    style EndPO fill:#e1ffe1
    style Approved fill:#ccffcc
    style PRRejected fill:#ffcccc