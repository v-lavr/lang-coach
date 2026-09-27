from fastapi import FastAPI
from pydantic import BaseModel
from transformers import AutoTokenizer, AutoModelForSeq2SeqLM
from difflib import SequenceMatcher
import re

MODEL_NAME = "thenHung/english-grammar-error-correction-t5-seq2seq"

app = FastAPI()

print("Loading grammar model...")

tokenizer = AutoTokenizer.from_pretrained(MODEL_NAME)
model = AutoModelForSeq2SeqLM.from_pretrained(MODEL_NAME)

print("Grammar model loaded.")


class CorrectionRequest(BaseModel):
    text: str


class CorrectionResponse(BaseModel):
    corrected: str
    errors: list["CorrectionError"]


class CorrectionError(BaseModel):
    type: str
    original: str
    replacement: str
    explanation: str | None = None


def describe_change(original: str, replacement: str, previous_word: str | None) -> CorrectionError:
    if previous_word and previous_word.lower() in {"didn't", "did"}:
        return CorrectionError(type="verb_form", original=original, replacement=replacement, explanation="Use the base verb after did/didn't.")
    if original.lower() in {"in", "on", "at", "to", "for", "about"} or replacement.lower() in {"in", "on", "at", "to", "for", "about"}:
        return CorrectionError(type="preposition", original=original, replacement=replacement, explanation="Use the corrected preposition in this context.")
    if original.lower() in {"a", "an", "the"} or replacement.lower() in {"a", "an", "the"}:
        return CorrectionError(type="article", original=original, replacement=replacement, explanation="Use the corrected article in this context.")
    return CorrectionError(type="grammar", original=original, replacement=replacement, explanation="Use the corrected form in this context.")


def extract_errors(original: str, corrected: str) -> list[CorrectionError]:
    original_tokens = re.findall(r"\w+(?:['’]\w+)?|[^\w\s]", original)
    corrected_tokens = re.findall(r"\w+(?:['’]\w+)?|[^\w\s]", corrected)
    errors: list[CorrectionError] = []

    for tag, start, end, replacement_start, replacement_end in SequenceMatcher(None, original_tokens, corrected_tokens).get_opcodes():
        if tag == "equal":
            continue
        changed = " ".join(original_tokens[start:end])
        replacement = " ".join(corrected_tokens[replacement_start:replacement_end])
        previous_word = original_tokens[start - 1] if start > 0 else None
        errors.append(describe_change(changed, replacement, previous_word))

    return errors


@app.post("/correct", response_model=CorrectionResponse)
def correct(request: CorrectionRequest):
    inputs = tokenizer(request.text, return_tensors="pt")

    outputs = model.generate(
        **inputs,
        max_new_tokens=128,
    )

    corrected = tokenizer.decode(
        outputs[0],
        skip_special_tokens=True,
    )

    return CorrectionResponse(corrected=corrected, errors=extract_errors(request.text, corrected))
