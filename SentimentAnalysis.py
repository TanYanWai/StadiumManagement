import sys
import json
from transformers import pipeline

# Load the sentiment analysis pipeline
sentiment_analyzer = pipeline("sentiment-analysis")

def analyze_sentiment(feedback_list):
    positive_count = 0
    neutral_count = 0
    negative_count = 0

    # Analyze sentiment for each feedback
    for feedback in feedback_list:
        result = sentiment_analyzer(feedback)[0]
        if result['label'] == 'POSITIVE':
            positive_count += 1
        elif result['label'] == 'NEGATIVE':
            negative_count += 1
        else:
            neutral_count += 1

    return {
        'positive': positive_count,
        'neutral': neutral_count,
        'negative': negative_count
    }

if __name__ == "__main__":
    try:
        feedback_data = json.loads(sys.argv[1])
        sentiment_counts = analyze_sentiment(feedback_data)
        print(json.dumps(sentiment_counts))
    except Exception as e:
        print(f"Error: {str(e)}", file=sys.stderr)
        print(json.dumps({'positive': 0, 'neutral': 0, 'negative': 0}))
