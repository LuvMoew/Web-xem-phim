<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Netflix FAQ</title>
   
    <link rel="stylesheet" href="../assets/css/FAQ.css">
</head>
<body>
    <div class="container">
        <h1>Câu hỏi thường gặp</h1>
        
        <div class="faq-list">
            <div class="faq-item">
                <div class="faq-question">
                    PhimFlix là gì?
                    <div class="icon"></div>
                </div>
                <div class="faq-answer">
                    Flix là dịch vụ phát trực tuyến mang đến đa dạng các loại chương trình truyền hình, phim, anime, phim tài liệu đoạt giải thưởng và nhiều nội dung khác trên hàng nghìn thiết bị có kết nối Internet.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    Tôi phải trả bao nhiêu tiền để trải nghiệm Flix?
                    <div class="icon"></div>
                </div>
                <div class="faq-answer">
                    Xem Flix với mức giá thấp từ 70.000₫/tháng. Không phát sinh phí phụ trội, không hợp đồng.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    Tôi có thể xem ở đâu?
                    <div class="icon"></div>
                </div>
                <div class="faq-answer">
                    Xem mọi lúc, mọi nơi. Đăng nhập bằng tài khoản Flix của bạn để xem ngay trên trang web Flix.com từ máy tính cá nhân, hoặc trên bất kỳ thiết bị nào có kết nối Internet.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    Làm thế nào để hủy?
                    <div class="icon"></div>
                </div>
                <div class="faq-answer">
                    Flix linh hoạt. Không có hợp đồng phiền toái, không ràng buộc. Bạn có thể dễ dàng hủy tài khoản trực tuyến chỉ trong 2 cú nhấp chuột.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    Tôi có thể xem gì trên Flix?
                    <div class="icon"></div>
                </div>
                <div class="faq-answer">
                    Netflix có thư viện phong phú gồm các phim truyện, phim tài liệu, chương trình truyền hình, anime, tác phẩm giành giải thưởng của Netflix và nhiều nội dung khác. Xem không giới hạn bất cứ lúc nào bạn muốn.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    Flix có phù hợp cho trẻ em không?
                    <div class="icon"></div>
                </div>
                <div class="faq-answer">
                    Trải nghiệm Flix Trẻ em có sẵn trong gói dịch vụ của bạn, trao cho phụ huynh quyền kiểm soát trong khi các em có thể thưởng thức các bộ phim và chương trình phù hợp cho gia đình tại không gian riêng.
                </div>
            </div>
        </div>

        <div class="signup-section">
            <p class="signup-text">Bạn đã sẵn sàng xem chưa? Nhập email để tạo hoặc kích hoạt lại tư cách thành viên của bạn.</p>
            <form class="signup-form">
                <input type="email" class="email-input" placeholder="Địa chỉ email" required>
                <button type="submit" class="start-button">
                    Bắt đầu
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 18l6-6-6-6"/>
                    </svg>
                </button>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const faqItems = document.querySelectorAll('.faq-item');
            
            faqItems.forEach(item => {
                const question = item.querySelector('.faq-question');
                
                question.addEventListener('click', () => {
                    // Close all other items
                    faqItems.forEach(otherItem => {
                        if (otherItem !== item && otherItem.classList.contains('active')) {
                            otherItem.classList.remove('active');
                            const answer = otherItem.querySelector('.faq-answer');
                            answer.style.maxHeight = null;
                        }
                    });
                    
                    // Toggle current item
                    item.classList.toggle('active');
                    const answer = item.querySelector('.faq-answer');
                    
                    if (item.classList.contains('active')) {
                        answer.style.display = 'block';
                        // Add small delay to allow display change before animation
                        setTimeout(() => {
                            answer.style.maxHeight = answer.scrollHeight + 'px';
                        }, 10);
                    } else {
                        answer.style.maxHeight = null;
                        // Remove display after animation
                        setTimeout(() => {
                            answer.style.display = 'none';
                        }, 300);
                    }
                });
            });

            // Form submission
            const form = document.querySelector('.signup-form');
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                const email = form.querySelector('.email-input').value;
                // Add your form submission logic here
                console.log('Form submitted with email:', email);
            });
        });
    </script>
</body>
</html>