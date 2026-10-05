# 🛡️ Insurance Claim Fraud Detection System

A web-based **Insurance Claim Fraud Detection and Management System** designed to simplify insurance claim processing while helping identify potentially fraudulent claims using **Machine Learning**.

The system provides a complete workflow from **claim submission and fraud analysis to administrative verification, approval/rejection, status tracking, and report generation**.

---

## 📌 Project Overview

Insurance companies receive a large number of claims, and manually identifying suspicious or fraudulent claims can be time-consuming and difficult.

This project provides a digital platform where users can submit insurance claims and administrators can review, analyze, and manage them through a centralized dashboard.

A **Machine Learning-based fraud detection module** analyzes claim information and assists in identifying claims that may require further investigation.

---

## 🎯 Objectives

- Automate the insurance claim submission process.
- Detect potentially fraudulent claims using Machine Learning.
- Provide an efficient admin dashboard for claim management.
- Allow administrators to approve or reject claims.
- Track claim status throughout the process.
- Generate claim-related reports.
- Reduce manual effort in preliminary fraud analysis.
- Provide a structured and user-friendly claim management workflow.

---

## ✨ Key Features

### 👤 User Module
- User-friendly claim submission interface
- Submit insurance claim details
- View submitted claim information
- Check claim status
- Access claim-related information

### 🔐 Admin Module
- Admin login
- Centralized admin dashboard
- View and analyze submitted claims
- Review fraud detection results
- Approve or reject claims
- Update claim status
- Manage claim records

### 🤖 Fraud Detection Module
- Machine Learning-based fraud prediction
- Claim data analysis
- Fraud probability/risk evaluation
- Prediction results integrated with the claim workflow
- Supports administrators in identifying suspicious claims

### 📊 Reporting & Tracking
- Claim status tracking
- Claim analysis
- Report generation
- Structured claim information
- Downloadable reports

---

## 🛠️ Technologies Used

| Technology | Purpose |
|---|---|
| **PHP** | Backend and server-side processing |
| **MySQL** | Database management |
| **HTML5** | Web page structure |
| **CSS3** | User interface styling |
| **JavaScript** | Client-side functionality |
| **Python** | Machine Learning and prediction |
| **Jupyter Notebook** | Model development and experimentation |
| **JSON** | Data exchange and prediction results |

---

## 🏗️ System Architecture

```text
                    ┌─────────────────────┐
                    │       User          │
                    └──────────┬──────────┘
                               │
                               ▼
                    ┌─────────────────────┐
                    │  Claim Submission   │
                    │     Interface       │
                    └──────────┬──────────┘
                               │
                               ▼
                    ┌─────────────────────┐
                    │    PHP Backend      │
                    └──────────┬──────────┘
                               │
                 ┌─────────────┴─────────────┐
                 ▼                           ▼
       ┌─────────────────┐         ┌─────────────────┐
       │     MySQL       │         │ ML Fraud Model  │
       │    Database     │         │    (Python)     │
       └─────────────────┘         └────────┬────────┘
                                            │
                                            ▼
                                  ┌──────────────────┐
                                  │ Fraud Prediction │
                                  └────────┬─────────┘
                                           │
                                           ▼
                                  ┌──────────────────┐
                                  │ Admin Dashboard  │
                                  └────────┬─────────┘
                                           │
                              ┌────────────┼────────────┐
                              ▼            ▼            ▼
                          Approved      Rejected     Pending
```

---

## 📂 Project Structure

```text
Insurance-claim-fraud-detection-system/
│
├── Claim Submission Page.html
├── firstpage.html
├── login.html
├── user-dashboard.html
│
├── admin.php
├── admin_dashboard.html
├── submit_claim.php
├── get_claims.php
├── query_claim.php
├── check_status.php
├── update_status.php
├── approve.php
├── reject.php
│
├── db.php
├── generate_report.php
├── download_report.php
│
├── train_model.py
├── predict_fraud.py
├── fraud_detection.ipynb
├── fraud_model.pkl
│
├── insurance_claims_dataset.csv
├── output.json
│
└── README.md
```

---

## 🔄 Application Workflow

```text
User
  ↓
Submit Insurance Claim
  ↓
Claim Data Stored
  ↓
Fraud Detection Analysis
  ↓
Fraud Prediction / Risk Evaluation
  ↓
Admin Dashboard
  ↓
Claim Review
  ↓
Approve / Reject / Update Status
  ↓
Generate Report
  ↓
Track Claim Status
```

---

## 🤖 Machine Learning Component

The Machine Learning component is responsible for analyzing insurance claim data and generating fraud predictions.

The project includes:

- `train_model.py` – Used for model training.
- `predict_fraud.py` – Used for fraud prediction.
- `fraud_detection.ipynb` – Used for experimentation and analysis.
- `fraud_model.pkl` – Trained Machine Learning model.
- `insurance_claims_dataset.csv` – Dataset used for analysis/model development.

The prediction output can then be used as an additional decision-support factor during claim review.

> **Note:** Machine Learning predictions are intended to assist claim analysis and should not be treated as the sole basis for a final insurance decision.

---

## 🗄️ Database

The system uses **MySQL** for storing and managing claim-related information.

The database layer supports operations such as:

- Storing claim details
- Retrieving claims
- Updating claim status
- Managing approval/rejection information
- Supporting claim tracking

---

## 💻 Local Setup

### Prerequisites

Install the following:

- XAMPP
- PHP
- MySQL
- Python 3.x
- Web browser
- Jupyter Notebook (optional)

### Setup Steps

1. Clone or download the repository.

2. Place the project folder inside the XAMPP `htdocs` directory:

```text
xampp/
└── htdocs/
    └── Insurance-claim-fraud-detection-system/
```

3. Start **Apache** and **MySQL** from XAMPP Control Panel.

4. Create the required MySQL database using phpMyAdmin.

5. Configure the database connection in:

```text
db.php
```

6. Install the required Python libraries for the Machine Learning component.

7. Run the application through the local Apache server.

Example:

```text
http://localhost/Insurance-claim-fraud-detection-system/
```

---

## 🔒 Security Considerations

For production deployment, the system should additionally implement:

- Secure password hashing
- Authentication and authorization
- Input validation and sanitization
- Prepared SQL statements
- Environment variables for database credentials
- Secure session management
- HTTPS
- Proper access control for administrative functions

---

## 🚀 Future Enhancements

Possible future improvements include:

- Advanced Machine Learning models
- Real-time fraud risk scoring
- Improved fraud analytics dashboard
- Email/SMS claim notifications
- Role-based access control
- Cloud deployment
- REST API integration
- Automated document verification
- Explainable AI for fraud predictions
- Real-time monitoring and analytics
- Enhanced security and audit logging

---

## 🎓 Learning Outcomes

Through this project, I developed practical experience in:

- PHP web development
- MySQL database management
- HTML, CSS and JavaScript
- Python Machine Learning
- Data preprocessing and model development
- Backend integration
- CRUD operations
- Claim workflow management
- Dashboard development
- Connecting Machine Learning with a web application

---

## 📌 Project Highlights

**Project Type:** Web Application + Machine Learning  
**Domain:** Insurance / FinTech  
**Backend:** PHP  
**Database:** MySQL  
**Frontend:** HTML, CSS, JavaScript  
**Machine Learning:** Python  
**Development Environment:** XAMPP  

---

## 👩‍💻 Author

**Vaishnavi Vinod Kamble**

Computer Science Engineering Student  
Interested in **Java, Web Development, Machine Learning, DSA, and Software Development**.

---

## ⭐ Acknowledgement

This project was developed as an academic and practical learning project to explore the integration of **web technologies, databases, and Machine Learning** for insurance claim analysis and fraud detection.

If you find this project useful or interesting, consider giving the repository a ⭐.
