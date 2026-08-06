import pandas as pd
from sklearn.ensemble import RandomForestClassifier
import joblib
import os

def main():
    print("Loading dataset...")
    file_path = 'insurance_claims_dataset.csv'
    if not os.path.exists(file_path):
        print(f"Error: {file_path} not found.")
        return

    df = pd.read_csv(file_path)
    
    # Feature selection - using numerical features that map easily to our DB
    features = ['vehicle_age', 'claim_amount', 'previous_claims', 'days_to_claim']
    
    # Verify features exist
    for f in features:
        if f not in df.columns:
            print(f"Error: Feature '{f}' not found in dataset.")
            return
            
    X = df[features]
    y = df['fraud']
    
    print("Training Random Forest model...")
    # Limiting depth to prevent overfitting and make it more generalized
    model = RandomForestClassifier(n_estimators=100, random_state=42, max_depth=6)
    model.fit(X, y)
    
    model_path = 'fraud_model.pkl'
    joblib.dump(model, model_path)
    print(f"Model trained successfully! Saved to {model_path}")

if __name__ == "__main__":
    main()
