 // By : Saadah Badriah
 module.exports = async function(body, args, arg, client, pushname, from, m) {
    // write your code reply here hereeee, Don't delete above. example :
    const fetch = require('node-fetch')
    var apiKey = 'R!dp3d' // change with your key self.
    var urlApi = 'https://www.api.ridped.com/api'
    var body = body.toLowerCase()

    

    const sections = [
        {
        title: "Section 1",
        rows: [
            {title: "Option 1", rowId: "option1"},
            {title: "Option 2", rowId: "option2", description: "This is a description"}
        ]
        },
       {
        title: "Section 2",
        rows: [
            {title: "Option 3", rowId: "option3"},
            {title: "Option 4", rowId: "option4", description: "This is a description V2"}
        ]
        },
    ]

    const listMessage = {
      text: "This is a list",
      footer: "nice footer, link: https://google.com",
      title: "Amazing boldfaced list title",
      buttonText: "Required, text on the button to view the list",
      sections
    }

    if (body == 'listbtn') {
        return await client.sendMessage(from, listMessage)
    }

    if (body.startsWith("628")) {
        var nameArr = body.split(',');
        var number = nameArr[0];
        var phonenumber = number + "@s.whatsapp.com";
        var student_name = nameArr[1];
        var rfid = nameArr[2];;
        const buttons = [
            {buttonId: 'status-,'. rfid, buttonText: {displayText: 'Already Pickup'}, type: 1}
          ]
          const buttonMessage = {
              text: "pick up for". student_name,
              footer: '',
              buttons: buttons,
              headerType: 1
          }
        return await client.sendMessage(phonenumber, buttonMessage)
    }

    if (body == 'halow') {
        // send message text
        return await client.sendMessage(from, { text: `Haloo ${pushname}`})
        // If reply mode then add { quoted: m }) so : return await client.sendMessage(from, { text: `Haloo ${pushname}`}, { quoted: m })
    }
    
    // auto reply with sesi
    if (body == 'kenalan') {
        client.kenalan = client.kenalan ? client.kenalan : {}
        if (from in client.kenalan) {
            return await client.sendMessage(from, { text: `The session isn't over yet, you haven't said your name`}, { quoted: m })
        }
        return client.kenalan[from] = [
            await client.sendMessage(from, { text: `Whats your name?` }, { quoted: m }),
            from
        ]
    }
    
    try {
        if (from in client.kenalan) {
            var name = m.message.conversation
            var noUser = client.kenalan[from][1]
            await client.sendMessage(noUser, { text: `Ohh hellow ${name}`}, { quoted: m })
            // if you want, you can insert to database value name & number.
            // delete sesi with this number
            return delete client.kenalan[noUser]
        }
    } catch (e) {}
    
    // game Guess the picture
    if (body == 'tebakgambar') {
        client.gtp = client.gtp ? client.gtp : {}
        if (from in client.gtp) {
            return await client.sendMessage(from, { text: `Masih ada tebakgambar yang belum anda jawab disini` }, { quoted: m })
        }
        await client.sendMessage(from, { text: 'Tunggu sebentar...' }, { quoted: m })
        var gtp = await fetch(urlApi + '?feature=tebakgambar&apikey=' + apiKey)
        var datjson = await gtp.json()
        var caption = `Silahkan jawab tebakgambar ini. Waktu untuk menjawab 25 detik!.
Bantuan : ${datjson.jawaban.replace(/[bcdfghjklmnpqrstvwxyz]/g, '_')}`
        if (datjson.status == false) {
            return await client.sendMessage(from, { text: datjson.msg })
        }
        return client.gtp[from] = [
            await client.sendMessage(from, { image: { url: datjson.img }, caption: caption, mimetype: 'image/jpeg' }),
            datjson,
            setTimeout(() => {
                if (client.gtp[from]) client.sendMessage(from, { text: `Waktu telah habis, jawabanya adalah : ${datjson.jawaban}`})
                return delete client.gtp[from]
            }, 25000)
        ]
    }
    
    try {
        if (from in client.gtp) {
            var j_user = m.message.conversation
            var jawaban = client.gtp[from][1].jawaban
            if (j_user.toLowerCase() == jawaban.toLowerCase()) {
                await client.sendMessage(from, { text: `Benar`}, { quoted: m})
                clearTimeout(client.gtp[from][2])
                return delete client.gtp[from]
            } else {
                return await client.sendMessage(from, { text: 'Salah' }, { quoted: m })
            }
        }
    } catch (e) {}
}
                
                