# mcping
给Minecraft服务器用的

应该支持基岩版、携带版和Java版最新-1.4

## 请求
GET和POST都可以

| 参数 | 说明 |
| --- | ----------- |
| address | 地址，不用多说 |
| port | 端口，不用多说 |
| ver | java 为 Java版，bedrock 为基岩版 |
| type | raw 为原始数据，decode 为方便使用点的，text 给人看的 |

## 响应
### type为raw
原始数据包
### type为text
返回格式
```
服务端版本: ...
协议版本: ...
MOTD: ...
最大玩家数量: ...
在线玩家数量: ...
```
### type为decode
#### Java版
##### 1.6以上
一个json，像这样:
```
{
    "version": {
        "name": "1.12.2",
        "protocol": 340
    },
    "players": {
        "max": 20,
        "online": 1,
        "sample": [
            {
                "name": "Steve",
                "id": "b6f4f39a-c981-4268-a15b-f29b42e4619f"
            }
        ]
    },
    "description": {
        "text": "A Minecraft Server"
    },
    "favicon": "data:image/png;base64,...",
    "enforcesSecureChat": false,
    "previewsChat": false
}
```
##### 1.6和1.6以下
使用 0x00 分割，顺序:
协议版本，版本，MOTD，在线玩家数量，最大玩家数量
#### 基岩版
一个JSON数组，顺序:
MOTD，协议版本，版本，在线玩家数量，最大玩家数量
