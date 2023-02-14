<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBangumiTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bangumi', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title')->default('')->comment('原始标题');
            $table->string('type')->default('')->comment('番组类型: tv web movie ova');
            $table->string('lang')->default('')->comment('语言： ja 日语 en 英文 zh-Hans 简体 zh-Hant 繁体');
            $table->text('official_site')->comment('官网');
            $table->integer('begin')->comment('tv/web: 番组开始时间; movie: 上映日期; ova: 首话发售时间');
            $table->integer('end')->comment('tv/web: 番组完结时间; movie: 无意义; ova: 则为最终话发售时间');
            $table->string('broadcast')->default('')->comment('放送周期');
            $table->integer('broadcast_begin')->comment('放送周期开始时间');
            $table->string('broadcast_frequency')->default('')->comment('放送频率：P0D 一次性 P7D 周播 P1D 日播 P1M 月播');
            $table->text('comment')->comment('备注');
            $table->integer('begin_search')->comment('搜索开始时间');
            $table->string('search_year')->default('')->comment('搜索年');
            $table->string('search_month')->default('')->comment('搜索月');
            $table->text('image')->comment('图片');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bangumi');
    }
}
