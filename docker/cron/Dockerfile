FROM alpine:3.19

# curlとタイムゾーンパッケージをインストール
RUN apk add --no-cache curl bash tzdata

# タイムゾーンを日本時間に設定
ENV TZ=Asia/Tokyo

# 実行スクリプトをコピー
COPY run-cron.sh /run-cron.sh
RUN chmod +x /run-cron.sh

# スクリプトを実行
CMD ["/run-cron.sh"]
