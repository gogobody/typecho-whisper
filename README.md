# typecho-whisper
typecho 时光机单页

![](https://cdn.jsdelivr.net/gh/gogobody/blog-img/blogimg/20210303225221.png)

> @author: 即刻学术 www.ijkxs.com
> 
> 单页适用于任何 typecho 主题。且不会对原来的主题产生任何影响。
## 基本信息

信息形式支持：文字、图片、地理位置、链接 (未完全测试)
发送模式支持：纯文字、纯图片、连续发送(混合模式)

修改自网上的 handsome 6.0 开心版。

本单页可接入QQ、微信、南博app等任何支持api开发的平台。

api 参考：[ljyngup博客](https://blog.ljyngup.com/archives/787.html/)

## 本单页使用教程
下载文件。拷贝文件夹`times`和文件`page.whisper.php`到你使用的主题目录下。  
新建单页，模板选择 超级时光机 。搞定。

## 相关接口（开发者可以看，新手略过）
API 使用事项。

1. 设置唯一的 time_code。给单页下面新加字段，字段名time_code，字段值自己填，将用于后面api唯一验证。

2. 默认开启可以评论。如果想关闭，给单页加字段enable_comment，值为0。

### 发送说说

#### 请求URL：

- `http(s)://博客地址/`

#### 请求方式：

- POST

#### 参数：

| 参数名        | 必选 | 数据类型 | 说明(所有参数涉及中文采用`UTF-8`编码)                        |
| :------------ | :--- | :------- | :----------------------------------------------------------- |
| **action**    | True | String   | 该值为`send_talk`                                            |
| **cid**       | True | String   | 时光机页面的cid值，后台可查看(也可利用该值为其他页面添加评论) |
| **time_code** | True | String   | 该值为后台时光机编码的MD5值(**32位十六进制小写**)，用于身份验证 |
| **token**     | True | String   | 将作为评论的agent字段，用于判断说说来源并显示(普通UA->系统版本，weixin->"微信公众号"，crx->"浏览器插件" 也可自己在`UA.php`中修改) |
| **msg_type**  | True | String   | 消息类型。有`text`(文字)、`link`(链接)、`image`(图片)、`location`(位置)、`mixed_talk`(图文混合说说) |
| **content**   | True | String   | 消息内容。为msg_type相对应的格式                             |

#### 返回字段：

| 返回字段 | 字段类型 | 说明                                                       |
| :------- | :------- | :--------------------------------------------------------- |
| status   | Int      | 返回结果状态。1：提交成功；-2：参数缺失 ; -3：身份验证失败 |

#### 补充说明：

- msg_type为`text`的内容可以包含HTML代码及短代码，会在前台解析
- msg_type为`image`的发送格式为：`图片网络地址`(图片会自动上传至博客，例如:https://cdn.jsdelivr.net/gh/iyear/blogpics@latest/usr/uploads/2019/12/801984719.png)
  如为本地图片先`上传图片`操作，再以`text`类型提交并手动加上`<img>`标签

**吐槽一下这里的操作逻辑，如果能在`typeImageContent`内加一个是否为本站图片的判断，并采取不重复上传的操作就好了**
[![sendtalk示例](https://cdn.jsdelivr.net/gh/iyear/blogpics@latest/usr/uploads/2019/12/801984719.png)](https://cdn.jsdelivr.net/gh/iyear/blogpics@latest/usr/uploads/2019/12/801984719.png)

[sendtalk示例](https://cdn.jsdelivr.net/gh/iyear/blogpics@latest/usr/uploads/2019/12/801984719.png)



- msg_type为`link`的发送格式为：`标题#描述#URL地址(不转义)`
  比如：`百度一下#来百度一下#https://www.baidu.com/`
- msg_type为`mixed_talk`的发送内容为UTF-8编码的JSON字符串
  例如：

```
{
    "results": [{
        "type": "text",
        "content": "test11"
    }, {
        "type": "text",
        "content": "test22"
    }, {
        "type": "image",
        "content": "https://cdn2.jianshu.io/assets/web/nav-logo-4c7bbafe27adc892f3046e6978459bac.png"
    }, {
        "type": "image",
        "content": "https://rescdn.qqmail.com/bizmail/zh_CN/htmledition/images/bizmail/new_login/exmail_logo_1473e91.png"
    }]
}
```

- msg_type为`location`的后台处理方式很奇怪。格式为`未知#未知#位置名称#地图图片`

1. 需要博客支持emoji，具体参考[YearCross文档](https://blog.ljyngup.com/go/aHR0cHM6Ly9kb2NzLmxqeW5ndXAuY29tL3llYXJjcm9zcy8jL3Byb2JsZW0|aWQ9dHlwZWNobyVlNiU4MCU4ZSVlNCViOSU4OCVlNiU5OCViZSVlNyVhNCViYWVtb2ppJWVmJWJjJTlm)
2. 地图图片通过调用高德地图的[静态地图API](https://blog.ljyngup.com/go/aHR0cHM6Ly9sYnMuYW1hcC5jb20vYXBpL3dlYnNlcnZpY2UvZ3VpZGUvYXBpL3N0YXRpY21hcHM=)实现的，最终会上传至本地服务器的`time`文件夹。
   接口不是很明朗，还是建议采用`text`提交。形式为`📌+位置名称+<img src="位置图片URL"/>`

**BB了这么多，其实我觉得这些类型没啥太大用处，直接在发送端处理完所有格式以`text`发送就行了**(而且服务端的处理明显只是主题作者自己用的接口)

------

### 上传图片

#### 请求URL：

- `http(s)://博客地址/`

#### 请求方式：

- POST

#### 参数：

| 参数名        | 必选  | 数据类型 | 说明(所有参数涉及中文采用`UTF-8`编码)                        |
| :------------ | :---- | :------- | :----------------------------------------------------------- |
| **action**    | True  | String   | 该值为`upload_img`                                           |
| **time_code** | True  | String   | 该值为后台时光机编码的MD5值(**32位十六进制小写**)，用于身份验证 |
| **file**      | True  | String   | 具体说明 ↓                                                   |
| **type**      | False | String   | 如`file`采取本地上传，可指定后缀例如`.png`。如该值为空默认为`.jpg` |

提交的file参数有两种形式：

**1.网络地址**(即图片URL地址，需包含完整http(s)头)
例如：`https://www.baidu.com/img/bd_logo1.png`
返回：`{"status":"1","data":"https:\/\/blog.ljyngup.com\/usr\/uploads\/time\/5e19aef89ebe2.jpg"}`
**2.本地图片**
采取BASE64编码并对"`+`"用`%2B`替代。对编码后的字符串加上前缀`data:image/(后缀名);base64,`(注意逗号)

例如我上传了一张CSDN的用户头像，对其编码后`file`参数为：`data:image/png;base64,iVBORw0KGgoAAAAN......`

#### 返回字段(JSON)：

| 返回字段 | 字段类型 | 说明                                                         |
| :------- | :------- | :----------------------------------------------------------- |
| status   | Int      | 返回结果状态。1：提交成功；-1：请求参数错误 ; -3：身份验证失败 |
| data     | String   | **上传成功后的图片地址**(对"/"转义，且所有网络图片均转码为`jpg`保存至`/time`文件夹，本地图片如果指定了后缀则使用该后缀) |

返回示例：`{"status":"1","data":"https:\/\/blog.ljyngup.com\/usr\/uploads\/time\/5e19a4cd41c71.jpg"}`

## 做一个自己的时光机机器人

todo