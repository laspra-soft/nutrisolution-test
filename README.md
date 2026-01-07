# 🛒 Cart Validation Test

## Technical Test - Backend PHP Developer

Implement a cart validation service with tax calculation and discount handling.

## 🚀 Installation

```bash
composer install
composer start
# → http://localhost:8080
```

## ✅ Verify Setup

```bash
curl http://localhost:8080/
# {"app":"Cart Validation Test","status":"running"}
```

## 🧪 Run Tests

```bash
composer test
```

## 📁 Project Structure

```
src/
├── Domain/           # Pure business logic (no dependencies)
│   ├── Entity/
│   ├── ValueObject/  # Money, Percentage
│   ├── Service/      # CartCalculator
│   └── Exception/
├── Application/      # Use cases and orchestration
│   ├── DTO/
│   ├── Service/      # DiscountService, TaxService
│   └── UseCase/
├── Infrastructure/
└── Presentation/
    └── Controller/
```

## 📖 Specifications

See `SPECIFICATIONS.md` for full details.

## 💡 Tips

1. Start with `Money` and `Percentage` (Value Objects)
2. Write tests first (TDD)
3. All amounts in **cents** (integers, no floats)
4. Domain layer has **zero external dependencies**

Good luck! 🍀
