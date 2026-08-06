import sys
import json
import joblib
import warnings
import os
import numpy as np

# Ignore scikit-learn warnings about version mismatches or feature names to keep stdout clean for JSON parsing
warnings.filterwarnings('ignore')

def get_feature_importance_and_impact(model, features_array, feature_names):
    """Calculate feature importance and impact on fraud score"""
    try:
        # Get feature importances from the trained model
        importances = model.feature_importances_
        
        # Normalize importances to percentages
        importance_pct = (importances / importances.sum()) * 100
        
        # Create feature analysis
        feature_analysis = []
        for i, (feature_name, importance, value) in enumerate(zip(feature_names, importance_pct, features_array[0])):
            # Determine risk contribution
            risk_contribution = ""
            if feature_name == "claim_amount":
                risk_contribution = "HIGH" if value > 50000 else ("MEDIUM" if value > 25000 else "LOW")
            elif feature_name == "previous_claims":
                risk_contribution = "HIGH" if value > 3 else ("MEDIUM" if value > 1 else "LOW")
            elif feature_name == "days_to_claim":
                risk_contribution = "HIGH" if value < 7 else ("MEDIUM" if value < 30 else "LOW")
            elif feature_name == "vehicle_age":
                risk_contribution = "HIGH" if value > 10 else ("MEDIUM" if value > 5 else "LOW")
            
            feature_analysis.append({
                "name": feature_name.replace("_", " ").title(),
                "value": float(value),
                "importance": round(float(importance), 1),
                "risk": risk_contribution
            })
        
        return sorted(feature_analysis, key=lambda x: x['importance'], reverse=True)
    except:
        return []

def main():
    if len(sys.argv) < 2:
        print(json.dumps({"error": "No data provided"}))
        return
        
    try:
        input_arg = sys.argv[1]
        
        # Check if the argument is a file path
        if os.path.isfile(input_arg):
            with open(input_arg, 'r') as f:
                claims = json.load(f)
        else:
            claims = json.loads(input_arg)
        
        # Load the trained model
        try:
            model = joblib.load('fraud_model.pkl')
        except Exception as e:
            print(json.dumps({"error": f"Failed to load model: {str(e)}"}))
            return
        
        feature_names = ['vehicle_age', 'claim_amount', 'previous_claims', 'days_to_claim']
        results = {}
        
        for claim in claims:
            cid = claim.get('claim_id')
            if not cid:
                continue
                
            # Extract features safely, with fallbacks
            try:
                vehicle_age = int(claim.get('vehicle_age', 5))
                claim_amount = float(claim.get('claim_amount', 10000))
                previous_claims = int(claim.get('previous_claims', 0))
                days_to_claim = int(claim.get('days_to_claim', 30))
            except (ValueError, TypeError):
                vehicle_age = 5
                claim_amount = 10000
                previous_claims = 0
                days_to_claim = 30
                
            features = [[vehicle_age, claim_amount, previous_claims, days_to_claim]]
            
            # Predict probability of fraud
            fraud_prob = model.predict_proba(features)[0][1]  # Probability of class 1 (fraud)
            fraud_percentage = round(fraud_prob * 100, 1)
            
            # Determine risk level (different from status)
            if fraud_percentage < 25:
                risk_level = "LOW"
                status = "SAFE"
            elif fraud_percentage < 50:
                risk_level = "MEDIUM"
                status = "SUSPICIOUS"
            else:
                risk_level = "HIGH"
                status = "FRAUD"
            
            # Get feature analysis
            feature_analysis = get_feature_importance_and_impact(model, features, feature_names)
            
            results[cid] = {
                "fraud_probability": fraud_percentage,
                "risk_level": risk_level,
                "status": status,
                "confidence": round(max(fraud_prob, 1 - fraud_prob) * 100, 1),
                "features": feature_analysis,
                "prediction_reasoning": f"Model predicts {fraud_percentage}% fraud risk based on claim analysis"
            }
            
        # Return strict JSON format on stdout
        print(json.dumps(results))
        
    except Exception as e:
        print(json.dumps({"error": str(e)}))

if __name__ == "__main__":
    main()
